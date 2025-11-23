const express = require('express');
const axios = require('axios');
const { verifyToken } = require('../middleware/auth');
const router = express.Router();

// ==================== REPORTE VENTAS ====================

router.get('/ventas', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.REPORTS_SERVICE}/api/reports/ventas`,
            {
                headers: { Authorization: token },
                params: req.query,
                timeout: 30000,
                responseType: req.query.formato === 'excel' ? 'arraybuffer' : 'stream'
            }
        );

        if (req.query.formato === 'excel') {
            res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            res.setHeader('Content-Disposition', 'attachment; filename="Reporte_Ventas.xlsx"');
        } else {
            res.setHeader('Content-Type', 'application/pdf');
            res.setHeader('Content-Disposition', 'attachment; filename="Reporte_Ventas.pdf"');
        }

        res.send(response.data);
    } catch (error) {
        console.error('Error al conectar con Reports Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al generar reporte'
        });
    }
});

// ==================== REPORTE ÓRDENES ====================

router.get('/ordenes', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.REPORTS_SERVICE}/api/reports/ordenes`,
            {
                headers: { Authorization: token },
                params: req.query,
                timeout: 30000,
                responseType: req.query.formato === 'excel' ? 'arraybuffer' : 'stream'
            }
        );

        if (req.query.formato === 'excel') {
            res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            res.setHeader('Content-Disposition', 'attachment; filename="Reporte_Ordenes.xlsx"');
        } else {
            res.setHeader('Content-Type', 'application/pdf');
            res.setHeader('Content-Disposition', 'attachment; filename="Reporte_Ordenes.pdf"');
        }

        res.send(response.data);
    } catch (error) {
        console.error('Error al conectar con Reports Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al generar reporte'
        });
    }
});

// ==================== DASHBOARD ====================

router.get('/dashboard', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.REPORTS_SERVICE}/api/reports/dashboard`,
            {
                headers: { Authorization: token },
                params: req.query,
                timeout: 30000,
                responseType: req.query.formato === 'excel' ? 'arraybuffer' : 'stream'
            }
        );

        if (req.query.formato === 'excel') {
            res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            res.setHeader('Content-Disposition', 'attachment; filename="Dashboard.xlsx"');
        } else {
            res.setHeader('Content-Type', 'application/pdf');
            res.setHeader('Content-Disposition', 'attachment; filename="Dashboard.pdf"');
        }

        res.send(response.data);
    } catch (error) {
        console.error('Error al conectar con Reports Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al generar dashboard'
        });
    }
});

module.exports = router;
