const express = require('express');
const axios = require('axios');
const router = express.Router();

// ==================== LOGIN ====================

router.post('/login', async (req, res) => {
    try {
        const response = await axios.post(
            `${process.env.AUTH_SERVICE}/api/auth/login`,
            req.body,
            { timeout: 5000 }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Auth Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error en autenticación'
        });
    }
});

// ==================== REGISTER ====================

router.post('/register', async (req, res) => {
    try {
        const response = await axios.post(
            `${process.env.AUTH_SERVICE}/api/auth/register`,
            req.body,
            { timeout: 5000 }
        );

        res.status(201).json(response.data);
    } catch (error) {
        console.error('Error al conectar con Auth Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error en registro'
        });
    }
});

// ==================== LOGOUT ====================

router.post('/logout', async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.post(
            `${process.env.AUTH_SERVICE}/api/auth/logout`,
            {},
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Auth Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error en logout'
        });
    }
});

// ==================== ME (Usuario actual) ====================

router.get('/me', async (req, res) => {
    try {
        const token = req.headers.authorization;
        const response = await axios.get(
            `${process.env.AUTH_SERVICE}/api/auth/me`,
            {
                headers: { Authorization: token },
                timeout: 5000
            }
        );

        res.json(response.data);
    } catch (error) {
        console.error('Error al conectar con Auth Service:', error.message);
        res.status(error.response?.status || 500).json({
            success: false,
            message: error.response?.data?.message || 'Error al obtener usuario'
        });
    }
});

module.exports = router;
