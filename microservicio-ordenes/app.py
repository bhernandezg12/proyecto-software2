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
MONGO_DB_NAME = os.getenv('MONGO_DB_NAME', 'orders_db')
JWT_SECRET = os.getenv('JWT_SECRET', 'secret')
AUTH_SERVICE_URL = os.getenv('AUTH_SERVICE_URL', 'http://localhost:8001')

# Conexión a MongoDB
client = MongoClient(MONGO_URI)
db = client[MONGO_DB_NAME]
workorders_collection = db['workorders']

# Crear índices
workorders_collection.create_index('numero_orden')
workorders_collection.create_index('cliente_id')
workorders_collection.create_index('estado')
workorders_collection.create_index('tecnico_asignado')
workorders_collection.create_index('fecha_creacion')

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
        'message': 'Microservicio de Órdenes de Trabajo funcionando',
        'timestamp': datetime.now().isoformat()
    })

@app.route('/api/workorders', methods=['GET'])
@require_auth
def list_workorders():
    """Listar todas las órdenes de trabajo con filtros"""
    try:
        # Parámetros de filtro
        page = request.args.get('page', 1, type=int)
        per_page = request.args.get('per_page', 10, type=int)
        estado = request.args.get('estado')
        cliente_id = request.args.get('cliente_id')
        tecnico_asignado = request.args.get('tecnico_asignado')
        
        # Construcción de filtro
        query = {}
        if estado:
            query['estado'] = estado
        if cliente_id:
            query['cliente_id'] = cliente_id
        if tecnico_asignado:
            query['tecnico_asignado'] = tecnico_asignado
        
        # Paginación
        skip = (page - 1) * per_page
        total = workorders_collection.count_documents(query)
        
        workorders = list(workorders_collection.find(query).skip(skip).limit(per_page))
        
        # Convertir ObjectId a string
        for order in workorders:
            order['_id'] = str(order['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Órdenes de trabajo obtenidas correctamente',
            'data': workorders,
            'pagination': {
                'page': page,
                'per_page': per_page,
                'total': total,
                'pages': (total + per_page - 1) // per_page
            }
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders', methods=['POST'])
@require_auth
def create_workorder():
    """Crear nueva orden de trabajo"""
    try:
        data = request.get_json()
        
        # Validación
        required_fields = ['numero_orden', 'cliente_id', 'cliente_nombre', 'descripcion']
        for field in required_fields:
            if not data.get(field):
                return jsonify({
                    'success': False,
                    'message': f'Campo requerido: {field}'
                }), 400
        
        # Crear orden de trabajo
        workorder = {
            'numero_orden': data['numero_orden'],
            'cliente_id': data['cliente_id'],
            'cliente_nombre': data['cliente_nombre'],
            'descripcion': data['descripcion'],
            'estado': data.get('estado', 'pendiente'),  # pendiente, en_progreso, completada, cancelada
            'prioridad': data.get('prioridad', 'media'),  # baja, media, alta
            'tecnico_asignado': data.get('tecnico_asignado'),
            'usuario_creador_id': request.user.get('id'),
            'fecha_creacion': datetime.now().isoformat(),
            'fecha_programada': data.get('fecha_programada'),
            'fecha_inicio': None,
            'fecha_finalizacion': None,
            'horas_estimadas': data.get('horas_estimadas', 0),
            'horas_trabajadas': 0,
            'notas': data.get('notas', ''),
            'ubicacion': data.get('ubicacion', ''),
            'telefonos_contacto': data.get('telefonos_contacto', []),
            'tareas': []
        }
        
        result = workorders_collection.insert_one(workorder)
        workorder['_id'] = str(result.inserted_id)
        
        return jsonify({
            'success': True,
            'message': 'Orden de trabajo creada exitosamente',
            'data': workorder
        }), 201
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders/<order_id>', methods=['GET'])
@require_auth
def get_workorder(order_id):
    """Obtener una orden de trabajo específica"""
    try:
        workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        
        if not workorder:
            return jsonify({
                'success': False,
                'message': 'Orden de trabajo no encontrada'
            }), 404
        
        workorder['_id'] = str(workorder['_id'])
        
        return jsonify({
            'success': True,
            'data': workorder
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders/<order_id>', methods=['PUT'])
@require_auth
def update_workorder(order_id):
    """Actualizar una orden de trabajo"""
    try:
        data = request.get_json()
        
        workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        if not workorder:
            return jsonify({
                'success': False,
                'message': 'Orden de trabajo no encontrada'
            }), 404
        
        # Campos permitidos para actualizar
        allowed_fields = ['estado', 'prioridad', 'tecnico_asignado', 'notas', 
                         'horas_trabajadas', 'fecha_inicio', 'fecha_finalizacion',
                         'descripcion', 'ubicacion', 'telefonos_contacto']
        update_data = {key: data[key] for key in allowed_fields if key in data}
        
        if update_data:
            update_data['fecha_actualizacion'] = datetime.now().isoformat()
            workorders_collection.update_one(
                {'_id': ObjectId(order_id)},
                {'$set': update_data}
            )
        
        updated_workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        updated_workorder['_id'] = str(updated_workorder['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Orden de trabajo actualizada exitosamente',
            'data': updated_workorder
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders/<order_id>', methods=['DELETE'])
@require_auth
def delete_workorder(order_id):
    """Eliminar una orden de trabajo"""
    try:
        workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        if not workorder:
            return jsonify({
                'success': False,
                'message': 'Orden de trabajo no encontrada'
            }), 404
        
        workorders_collection.delete_one({'_id': ObjectId(order_id)})
        
        return jsonify({
            'success': True,
            'message': 'Orden de trabajo eliminada exitosamente'
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders/<order_id>/assign', methods=['PATCH'])
@require_auth
def assign_technician(order_id):
    """Asignar técnico a una orden de trabajo"""
    try:
        data = request.get_json()
        
        if not data.get('tecnico_asignado'):
            return jsonify({
                'success': False,
                'message': 'tecnico_asignado es requerido'
            }), 400
        
        workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        if not workorder:
            return jsonify({
                'success': False,
                'message': 'Orden de trabajo no encontrada'
            }), 404
        
        workorders_collection.update_one(
            {'_id': ObjectId(order_id)},
            {'$set': {
                'tecnico_asignado': data['tecnico_asignado'],
                'fecha_asignacion': datetime.now().isoformat(),
                'fecha_actualizacion': datetime.now().isoformat()
            }}
        )
        
        updated_workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        updated_workorder['_id'] = str(updated_workorder['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Técnico asignado exitosamente',
            'data': updated_workorder
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders/<order_id>/status', methods=['PATCH'])
@require_auth
def update_status(order_id):
    """Cambiar estado de una orden de trabajo"""
    try:
        data = request.get_json()
        
        new_status = data.get('estado')
        if new_status not in ['pendiente', 'en_progreso', 'completada', 'cancelada']:
            return jsonify({
                'success': False,
                'message': 'Estado inválido. Debe ser: pendiente, en_progreso, completada, cancelada'
            }), 400
        
        workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        if not workorder:
            return jsonify({
                'success': False,
                'message': 'Orden de trabajo no encontrada'
            }), 404
        
        update_data = {
            'estado': new_status,
            'fecha_actualizacion': datetime.now().isoformat()
        }
        
        # Si cambia a en_progreso, registrar fecha de inicio
        if new_status == 'en_progreso' and not workorder.get('fecha_inicio'):
            update_data['fecha_inicio'] = datetime.now().isoformat()
        
        # Si cambia a completada, registrar fecha de finalización
        if new_status == 'completada':
            update_data['fecha_finalizacion'] = datetime.now().isoformat()
        
        workorders_collection.update_one(
            {'_id': ObjectId(order_id)},
            {'$set': update_data}
        )
        
        updated_workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        updated_workorder['_id'] = str(updated_workorder['_id'])
        
        return jsonify({
            'success': True,
            'message': f'Estado actualizado a {new_status}',
            'data': updated_workorder
        }), 200
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/workorders/<order_id>/add-task', methods=['POST'])
@require_auth
def add_task(order_id):
    """Agregar tarea a una orden de trabajo"""
    try:
        data = request.get_json()
        
        if not data.get('descripcion'):
            return jsonify({
                'success': False,
                'message': 'descripcion es requerida'
            }), 400
        
        workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        if not workorder:
            return jsonify({
                'success': False,
                'message': 'Orden de trabajo no encontrada'
            }), 404
        
        task = {
            'id': str(ObjectId()),
            'descripcion': data['descripcion'],
            'estado': data.get('estado', 'pendiente'),
            'fecha_creacion': datetime.now().isoformat(),
            'completada': False
        }
        
        workorders_collection.update_one(
            {'_id': ObjectId(order_id)},
            {'$push': {'tareas': task}}
        )
        
        updated_workorder = workorders_collection.find_one({'_id': ObjectId(order_id)})
        updated_workorder['_id'] = str(updated_workorder['_id'])
        
        return jsonify({
            'success': True,
            'message': 'Tarea agregada exitosamente',
            'data': updated_workorder
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
    app.run(debug=True, host='127.0.0.1', port=5001)
