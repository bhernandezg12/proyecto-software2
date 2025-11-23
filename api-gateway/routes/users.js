const express = require('express');
const axios = require('axios');
const { verifyToken } = require('../middleware/auth');
const router = express.Router();

// ==================== LISTAR USUARIOS ====================

router.get('/', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.USERS_SERVICE}/api/users`,
            {
                headers: { Authorization: token },
                params: req.query,
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Users Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener usuarios'
        });
    }
});

// ==================== OBTENER USUARIO ====================

router.get('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.USERS_SERVICE}/api/users/${req.params.id}`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Users Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener usuario'
        });
    }
});

// ==================== CREAR USUARIO ====================

router.post('/', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.post(
            `${process.env.USERS_SERVICE}/api/users`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.status(201).json(response.data);
    } catch (error) {
        console.error('Error al conectar con Users Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al crear usuario'
        });
    }
});

// ==================== ACTUALIZAR USUARIO ====================

router.put('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.put(
            `${process.env.USERS_SERVICE}/api/users/${req.params.id}`,
            req.body,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Users Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al actualizar usuario'
        });
    }
});

// ==================== ELIMINAR USUARIO ====================

router.delete('/:id', verifyToken, async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.delete(
            `${process.env.USERS_SERVICE}/api/users/${req.params.id}`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Users Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al eliminar usuario'
        });
    }
});

module.exports = router;
