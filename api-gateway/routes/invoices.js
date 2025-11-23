const express = require('express');
const axios = require('axios');
const { verifyToken } = require('../middleware/auth');
const router = express.Router();

// ==================== LISTAR FACTURAS ====================

router.get('/', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.INVOICES_SERVICE}/api/invoices`,
            {
                headers: { Authorization: token },
                params: req.query,
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Invoices Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener facturas'
        });
    }
});

// ==================== CREAR FACTURA ====================

router.post('/', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.post(
            `${process.env.INVOICES_SERVICE}/api/invoices`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.status(201).json(response.data);
    } catch (error) {
        console.error('Error al conectar con Invoices Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al crear factura'
        });
    }
});

// ==================== OBTENER FACTURA ====================

router.get('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.INVOICES_SERVICE}/api/invoices/${req.params.id}`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Invoices Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener factura'
        });
    }
});

// ==================== ACTUALIZAR FACTURA ====================

router.put('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.put(
            `${process.env.INVOICES_SERVICE}/api/invoices/${req.params.id}`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Invoices Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al actualizar factura'
        });
    }
});

// ==================== MARCAR COMO PAGADA ====================

router.patch('/:id/pay', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.patch(
            `${process.env.INVOICES_SERVICE}/api/invoices/${req.params.id}/pay`,
            {},
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Invoices Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al marcar factura como pagada'
        });
    }
});

// ==================== ELIMINAR FACTURA ====================

router.delete('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.delete(
            `${process.env.INVOICES_SERVICE}/api/invoices/${req.params.id}`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Invoices Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al eliminar factura'
        });
    }
});

module.exports = router;
