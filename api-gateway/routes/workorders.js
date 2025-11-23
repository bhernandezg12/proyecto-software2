const express = require('express');
const axios = require('axios');
const { verifyToken } = require('../middleware/auth');
const router = express.Router();

// ==================== LISTAR ÓRDENES ====================

router.get('/', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.WORKORDERS_SERVICE}/api/workorders`,
            {
                headers: { Authorization: token },
                params: req.query,
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener órdenes'
        });
    }
});

// ==================== CREAR ORDEN ====================

router.post('/', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.post(
            `${process.env.WORKORDERS_SERVICE}/api/workorders`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.status(201).json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al crear orden'
        });
    }
});

// ==================== OBTENER ORDEN ====================

router.get('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.WORKORDERS_SERVICE}/api/workorders/${req.params.id}`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener orden'
        });
    }
});

// ==================== ACTUALIZAR ORDEN ====================

router.put('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.put(
            `${process.env.WORKORDERS_SERVICE}/api/workorders/${req.params.id}`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al actualizar orden'
        });
    }
});

// ==================== ASIGNAR TÉCNICO ====================

router.patch('/:id/assign', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.patch(
            `${process.env.WORKORDERS_SERVICE}/api/workorders/${req.params.id}/assign`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al asignar técnico'
        });
    }
});

// ==================== CAMBIAR ESTADO ====================

router.patch('/:id/status', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.patch(
            `${process.env.WORKORDERS_SERVICE}/api/workorders/${req.params.id}/status`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al cambiar estado'
        });
    }
});

// ==================== AGREGAR TAREA ====================

router.post('/:id/tasks', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.post(
            `${process.env.WORKORDERS_SERVICE}/api/workorders/${req.params.id}/add-task`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al agregar tarea'
        });
    }
});

// ==================== ELIMINAR ORDEN ====================

router.delete('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.delete(
            `${process.env.WORKORDERS_SERVICE}/api/workorders/${req.params.id}`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Workorders Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al eliminar orden'
        });
    }
});

module.exports = router;
