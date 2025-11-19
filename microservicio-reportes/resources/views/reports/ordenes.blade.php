<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { text-align: center; padding: 10px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2196F3; color: white; }
    </style>
</head>
<body>
    <div class="header">REPORTE DE ÓRDENES DE TRABAJO</div>
    
    <div class="stats">
        <div class="stat-box">
            <strong>Total</strong><br>{{ $total_ordenes }}
        </div>
        <div class="stat-box">
            <strong>Completadas</strong><br>{{ $completadas }}
        </div>
        <div class="stat-box">
            <strong>Pendientes</strong><br>{{ $pendientes }}
        </div>
        <div class="stat-box">
            <strong>En Progreso</strong><br>{{ $en_progreso }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Técnico</th>
                <th>Estado</th>
                <th>Horas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordenes as $orden)
            <tr>
                <td>{{ $orden['numero_orden'] ?? 'N/A' }}</td>
                <td>{{ $orden['cliente_nombre'] ?? 'N/A' }}</td>
                <td>{{ $orden['tecnico_asignado'] ?? 'Sin asignar' }}</td>
                <td>{{ $orden['estado'] ?? 'N/A' }}</td>
                <td>{{ $orden['horas_trabajadas'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align: right; margin-top: 30px; color: #666;">
        Generado: {{ date('d/m/Y H:i') }}
    </p>
</body>
</html>
