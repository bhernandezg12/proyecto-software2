from flask import Flask, request, jsonify
from flask_cors import CORS
from pymongo import MongoClient
from datetime import datetime
import jwt
import requests
import os
from dotenv import load_dotenv
from bson.objectid import ObjectId

load_dotenv()

app = Flask(__name__)
CORS(app)

# Configuración
MONGO_URI = os.getenv('MONGO_URI', 'mongodb://localhost:27017/')
MONGO_DB_NAME = os.getenv('MONGO_DB_NAME', 'billing_db')
JWT_SECRET = os.getenv('JWT_SECRET', 'secret')
AUTH_SERVICE_URL = os.getenv('AUTH_SERVICE_URL', 'http://localhost:8001')

# Conexión a MongoDB
client = MongoClient(MONGO_URI)
db = client[MONGO_DB_NAME]
invoices_collection = db['invoices']

# Crear índices
invoices_collection.create_index('numero_factura')
invoices_collection.create_index('cliente_id')
invoices_collection.create_index('fecha_creacion')

# Middleware para validar JWT
def verify_token(token):
    """Verifica el token JWT del Auth Service"""
    try:
        if not token:
            return None, 'Token no proporcionado'
        
        # El token viene en formato "Bearer <token>"
        if token.startswith('Bearer '):
            token = token[7:]
        
        decoded = jwt.decode(token, JWT_SECRET, algorithms=['HS256'])
        return decoded, None
    except jwt.ExpiredSignatureError:
        return None, 'Token expirado'
    except jwt.InvalidTokenError:
        return None, 'Token inválido'

def require_auth(f):
    """Decorador para rutas protegidas"""
    def decorated_function(*args, **kwargs):
        auth_header = request.headers.get('Authorization')
        if not auth_header:
            return jsonify({'success': False, 'message': 'Token requerido'}), 401
        
        decoded, error = verify_token(auth_header)
        if error:
            return jsonify({'success': False, 'message': error}), 401
        
        request.user = decoded
        return f(*args, **kwargs)
    
    decorated_function.__name__ = f.__name__
    return decorated_function

# ==================== RUTAS ====================

@app.route('/api/health', methods=['GET'])
def health():
    """Verificar que el servicio está corriendo"""
    return jsonify({
        'success': True,
        'message': 'Microservicio de Facturación funcionando',
        'timestamp': datetime.now().isoformat()
    })

@app.route('/api/invoices', methods=['GET'])
@require_auth
def list_invoices():
    """Listar todas las facturas con filtros"""
    try:
        # Parámetros de filtro
        page = request.args.get('page', 1, type=int)
        per_page = request.args.get('per_page', 10, type=int)
        estado = request.args.get('estado')
        cliente_id = request.args.get('cliente_id')
        
        # Construcción de filtro
        query = {}
        if estado:
            query['estado'] = estado
        if cliente_id:
            query['cliente_id'] = cliente_id
        
        # Paginación
        skip = (page - 1) * per_page
        total = invoices_collection.count_documents(query)
        
        invoices = list(invoices_collection.find(query).skip(skip).limit(per_page))
        
        # Convertir ObjectId a string
        for invoice in invoices:
            invoice['_id'] = str(invoice['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Facturas obtenidas correctamente',
            'data': invoices,
            'pagination': {
                'page': page,
                'per_page': per_page,
                'total': total,
                'pages': (total + per_page - 1) // per_page
            }
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/invoices', methods=['POST'])
@require_auth
def create_invoice():
    """Crear nueva factura"""
    try:
        data = request.get_json()
        
        # Validación
        required_fields = ['numero_factura', 'cliente_id', 'cliente_nombre', 'items']
        for field in required_fields:
            if not data.get(field):
                return jsonify({
                    'success': False,
                    'message': f'Campo requerido: {field}'
                }), 400
        
        if not isinstance(data.get('items'), list) or len(data['items']) == 0:
            return jsonify({
                'success': False,
                'message': 'items debe ser una lista no vacía'
            }), 400
        
        # Cálcular totales
        subtotal = 0
        for item in data['items']:
            subtotal += item.get('cantidad', 0) * item.get('precio_unitario', 0)
        
        iva = subtotal * 0.19  # IVA 19%
        total = subtotal + iva
        
        # Crear factura
        invoice = {
            'numero_factura': data['numero_factura'],
            'cliente_id': data['cliente_id'],
            'cliente_nombre': data['cliente_nombre'],
            'items': data['items'],
            'subtotal': subtotal,
            'iva': iva,
            'total': total,
            'estado': data.get('estado', 'pendiente'),  # pendiente, pagada, cancelada
            'usuario_id': request.user.get('id'),
            'fecha_creacion': datetime.now().isoformat(),
            'fecha_pago': data.get('fecha_pago'),
            'notas': data.get('notas', '')
        }
        
        result = invoices_collection.insert_one(invoice)
        invoice['_id'] = str(result.inserted_id)
        
        return jsonify({
            'success': True,
            'message': 'Factura creada exitosamente',
            'data': invoice
        }), 201
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/invoices/<invoice_id>', methods=['GET'])
@require_auth
def get_invoice(invoice_id):
    """Obtener una factura específica"""
    try:
        invoice = invoices_collection.find_one({'_id': ObjectId(invoice_id)})
        
        if not invoice:
            return jsonify({
                'success': False,
                'message': 'Factura no encontrada'
            }), 404
        
        invoice['_id'] = str(invoice['_id'])
        
        return jsonify({
            'success': True,
            'data': invoice
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/invoices/<invoice_id>', methods=['PUT'])
@require_auth
def update_invoice(invoice_id):
    """Actualizar una factura"""
    try:
        data = request.get_json()
        
        invoice = invoices_collection.find_one({'_id': ObjectId(invoice_id)})
        if not invoice:
            return jsonify({
                'success': False,
                'message': 'Factura no encontrada'
            }), 404
        
        # Campos permitidos para actualizar
        allowed_fields = ['estado', 'notas', 'fecha_pago', 'cliente_nombre']
        update_data = {key: data[key] for key in allowed_fields if key in data}
        
        if update_data:
            update_data['fecha_actualizacion'] = datetime.now().isoformat()
            invoices_collection.update_one(
                {'_id': ObjectId(invoice_id)},
                {'$set': update_data}
            )
        
        updated_invoice = invoices_collection.find_one({'_id': ObjectId(invoice_id)})
        updated_invoice['_id'] = str(updated_invoice['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Factura actualizada exitosamente',
            'data': updated_invoice
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/invoices/<invoice_id>', methods=['DELETE'])
@require_auth
def delete_invoice(invoice_id):
    """Eliminar una factura"""
    try:
        invoice = invoices_collection.find_one({'_id': ObjectId(invoice_id)})
        if not invoice:
            return jsonify({
                'success': False,
                'message': 'Factura no encontrada'
            }), 404
        
        invoices_collection.delete_one({'_id': ObjectId(invoice_id)})
        
        return jsonify({
            'success': True,
            'message': 'Factura eliminada exitosamente'
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/invoices/<invoice_id>/pay', methods=['PATCH'])
@require_auth
def mark_as_paid(invoice_id):
    """Marcar factura como pagada"""
    try:
        invoice = invoices_collection.find_one({'_id': ObjectId(invoice_id)})
        if not invoice:
            return jsonify({
                'success': False,
                'message': 'Factura no encontrada'
            }), 404
        
        invoices_collection.update_one(
            {'_id': ObjectId(invoice_id)},
            {'$set': {
                'estado': 'pagada',
                'fecha_pago': datetime.now().isoformat(),
                'fecha_actualizacion': datetime.now().isoformat()
            }}
        )
        
        updated_invoice = invoices_collection.find_one({'_id': ObjectId(invoice_id)})
        updated_invoice['_id'] = str(updated_invoice['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Factura marcada como pagada',
            'data': updated_invoice
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

# ==================== MANEJO DE ERRORES ====================

@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'success': False,
        'message': 'Ruta no encontrada'
    }), 404

@app.errorhandler(500)
def server_error(error):
    return jsonify({
        'success': False,
        'message': 'Error interno del servidor'
    }), 500

# ==================== MAIN ====================

if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)
