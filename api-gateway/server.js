const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

const app = express();

// ==================== MIDDLEWARE ====================

// Seguridad
app.use(helmet());

// CORS
app.use(cors({
    origin: process.env.CORS_ORIGIN || '*',
    credentials: true
}));

// Body parser
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Rate limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutos
    max: 100 // límite de 100 peticiones por ventana
});
app.use('/api/', limiter);

// ==================== RUTAS ====================

// Health check
app.get('/health', (req, res) => {
    res.json({
        success: true,
        message: 'API Gateway funcionando',
        timestamp: new Date().toISOString()
    });
});

// Importar rutas
app.use('/api/auth', require('./routes/auth'));
app.use('/api/users', require('./routes/users'));
app.use('/api/invoices', require('./routes/invoices'));
app.use('/api/workorders', require('./routes/workorders'));
app.use('/api/reports', require('./routes/reports'));

// ==================== MANEJO DE ERRORES ====================

// 404
app.use((req, res) => {
    res.status(404).json({
        success: false,
        message: 'Ruta no encontrada',
        path: req.path
    });
});

// Error global
app.use((err, req, res, next) => {
    console.error('Error:', err);
    res.status(err.status || 500).json({
        success: false,
        message: err.message || 'Error interno del servidor'
    });
});

// ==================== INICIAR SERVIDOR ====================

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`✅ API Gateway corriendo en http://localhost:${PORT}`);
    console.log(`📡 Microservicios conectados:`);
    console.log(`   - Auth: ${process.env.AUTH_SERVICE}`);
    console.log(`   - Usuarios: ${process.env.USERS_SERVICE}`);
    console.log(`   - Facturación: ${process.env.INVOICES_SERVICE}`);
    console.log(`   - Órdenes: ${process.env.WORKORDERS_SERVICE}`);
    console.log(`   - Reportes: ${process.env.REPORTS_SERVICE}`);
});

module.exports = app;
