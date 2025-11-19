<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MongoDB\Client as MongoClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;

class ReportController extends Controller
{
    

    // ==================== REPORTES DE VENTAS ====================

    public function reporteVentas(Request $request)
    {
        try {
            $fecha_inicio = $request->query('fecha_inicio') ?? date('Y-01-01');
            $fecha_fin = $request->query('fecha_fin') ?? date('Y-m-d');
            $formato = $request->query('formato', 'pdf'); // pdf o excel

            // Conectar a MongoDB
            $mongo = new MongoClient(env('MONGODB_URI', 'mongodb://localhost:27017'));
            $db = $mongo->selectDatabase(env('MONGODB_DATABASE', 'billing_db'));
            $invoices = $db->selectCollection('invoices');

            // Consultar facturas en rango de fechas
            $filter = [
                'fecha_creacion' => [
                    '$gte' => $fecha_inicio . 'T00:00:00.000Z',
                    '$lte' => $fecha_fin . 'T23:59:59.999Z'
                ]
            ];

            $results = $invoices->find($filter)->limit(50)->toArray();

            // Calcular estadísticas
            $total_ingresos = 0;
            $total_facturas = 0;
            $cliente_top = [];
            $clientes = [];

            foreach ($results as $factura) {
                $total_ingresos += $factura['total'] ?? 0;
                $total_facturas++;
                $cliente = $factura['cliente_nombre'] ?? 'Sin cliente';
                $clientes[$cliente] = ($clientes[$cliente] ?? 0) + ($factura['total'] ?? 0);
            }

            if (!empty($clientes)) {
                $cliente_top = array_keys($clientes, max($clientes));
            }

            $data = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'total_ingresos' => $total_ingresos,
                'total_facturas' => $total_facturas,
                'cliente_top' => $cliente_top ?? 'N/A',
                'facturas' => $results,
                'promedio_factura' => $total_facturas > 0 ? $total_ingresos / $total_facturas : 0
            ];

            if ($formato === 'excel') {
                return $this->generarExcelVentas($data);
            } else {
                return $this->generarPdfVentas($data);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function generarExcelVentas($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte Ventas');

        // Encabezados
        $sheet->setCellValue('A1', 'REPORTE DE VENTAS');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Información general
        $sheet->setCellValue('A3', 'Período:');
        $sheet->setCellValue('B3', $data['fecha_inicio'] . ' al ' . $data['fecha_fin']);

        $sheet->setCellValue('A4', 'Total Ingresos:');
        $sheet->setCellValue('B4', '$' . number_format($data['total_ingresos'], 2));
        $sheet->getStyle('B4')->getFont()->setBold(true);

        $sheet->setCellValue('A5', 'Total Facturas:');
        $sheet->setCellValue('B5', $data['total_facturas']);

        $sheet->setCellValue('A6', 'Cliente Top:');
        $sheet->setCellValue('B6', $data['cliente_top']);

        $sheet->setCellValue('A7', 'Promedio Factura:');
        $sheet->setCellValue('B7', '$' . number_format($data['promedio_factura'], 2));

        // Tabla de facturas
        $sheet->setCellValue('A9', 'Número');
        $sheet->setCellValue('B9', 'Cliente');
        $sheet->setCellValue('C9', 'Total');
        $sheet->setCellValue('D9', 'Estado');

        $sheet->getStyle('A9:D9')->getFont()->setBold(true);

        $row = 10;
        foreach ($data['facturas'] as $factura) {
            $sheet->setCellValue('A' . $row, $factura['numero_factura'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $factura['cliente_nombre'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, '$' . number_format($factura['total'] ?? 0, 2));
            $sheet->setCellValue('D' . $row, $factura['estado'] ?? 'N/A');
            $row++;
        }

        // Ajustar anchos de columnas
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);

        // Descargar
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Reporte_Ventas_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $writer->save('php://output');
        exit;
    }

    private function generarPdfVentas($data)
    {
        $html = view('reports.ventas', $data)->render();
        $pdf = Pdf::loadHTML($html);
        return $pdf->download('Reporte_Ventas_' . date('Y-m-d') . '.pdf');
    }

    // ==================== REPORTES DE ÓRDENES ====================

    public function reporteOrdenes(Request $request)
    {
        try {
            $formato = $request->query('formato', 'pdf');

            // Conectar a MongoDB
            $mongo = new MongoClient(env('MONGODB_URI', 'mongodb://localhost:27017'));
            $db = $mongo->selectDatabase('orders_db');
            $workorders = $db->selectCollection('workorders');

            // Consultar órdenes
            $results = $workorders->find()->limit(50)->toArray();

            // Calcular estadísticas
            $completadas = 0;
            $pendientes = 0;
            $en_progreso = 0;
            $canceladas = 0;
            $tecnico_top = [];

            foreach ($results as $orden) {
                $estado = $orden['estado'] ?? 'pendiente';
                
                if ($estado === 'completada') $completadas++;
                elseif ($estado === 'pendiente') $pendientes++;
                elseif ($estado === 'en_progreso') $en_progreso++;
                elseif ($estado === 'cancelada') $canceladas++;

                $tecnico = $orden['tecnico_asignado'] ?? 'Sin asignar';
                $tecnico_top[$tecnico] = ($tecnico_top[$tecnico] ?? 0) + 1;
            }

            $data = [
                'total_ordenes' => count($results),
                'completadas' => $completadas,
                'pendientes' => $pendientes,
                'en_progreso' => $en_progreso,
                'canceladas' => $canceladas,
                'tecnico_top' => !empty($tecnico_top) ? array_key_first($tecnico_top) : 'N/A',
                'ordenes' => $results
            ];

            if ($formato === 'excel') {
                return $this->generarExcelOrdenes($data);
            } else {
                return $this->generarPdfOrdenes($data);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function generarExcelOrdenes($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte Órdenes');

        // Encabezados
        $sheet->setCellValue('A1', 'REPORTE DE ÓRDENES DE TRABAJO');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Estadísticas
        $sheet->setCellValue('A3', 'Total Órdenes:');
        $sheet->setCellValue('B3', $data['total_ordenes']);

        $sheet->setCellValue('A4', 'Completadas:');
        $sheet->setCellValue('B4', $data['completadas']);

        $sheet->setCellValue('A5', 'Pendientes:');
        $sheet->setCellValue('B5', $data['pendientes']);

        $sheet->setCellValue('A6', 'En Progreso:');
        $sheet->setCellValue('B6', $data['en_progreso']);

        $sheet->setCellValue('A7', 'Técnico Top:');
        $sheet->setCellValue('B7', $data['tecnico_top']);

        // Tabla de órdenes
        $sheet->setCellValue('A9', 'Número');
        $sheet->setCellValue('B9', 'Cliente');
        $sheet->setCellValue('C9', 'Técnico');
        $sheet->setCellValue('D9', 'Estado');
        $sheet->setCellValue('E9', 'Horas');
        $sheet->setCellValue('F9', 'Prioridad');

        $sheet->getStyle('A9:F9')->getFont()->setBold(true);

        $row = 10;
        foreach ($data['ordenes'] as $orden) {
            $sheet->setCellValue('A' . $row, $orden['numero_orden'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $orden['cliente_nombre'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $orden['tecnico_asignado'] ?? 'Sin asignar');
            $sheet->setCellValue('D' . $row, $orden['estado'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $orden['horas_trabajadas'] ?? 0);
            $sheet->setCellValue('F' . $row, $orden['prioridad'] ?? 'media');
            $row++;
        }

        // Ajustar anchos
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        // Descargar
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Reporte_Ordenes_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $writer->save('php://output');
        exit;
    }

    private function generarPdfOrdenes($data)
    {
        $html = view('reports.ordenes', $data)->render();
        $pdf = Pdf::loadHTML($html);
        return $pdf->download('Reporte_Ordenes_' . date('Y-m-d') . '.pdf');
    }

    // ==================== REPORTES DASHBOARD ====================

    public function dashboard(Request $request)
    {
        try {
            // MongoDB - Facturación
            $mongo = new MongoClient(env('MONGODB_URI', 'mongodb://localhost:27017'));
            
            $db_billing = $mongo->selectDatabase('billing_db');
            $invoices = $db_billing->selectCollection('invoices');
            $total_ventas = array_sum(array_column($invoices->find()->toArray(), 'total'));

            // MongoDB - Órdenes
            $db_orders = $mongo->selectDatabase('orders_db');
            $workorders = $db_orders->selectCollection('workorders');
            $total_ordenes = $workorders->countDocuments();

            // MySQL - Usuarios
            $total_usuarios = DB::table('users')->count();

            $data = [
                'total_ventas' => $total_ventas,
                'total_ordenes' => $total_ordenes,
                'total_usuarios' => $total_usuarios,
                'fecha' => date('d/m/Y H:i')
            ];

            $formato = $request->query('formato', 'pdf');
            if ($formato === 'excel') {
                return $this->generarExcelDashboard($data);
            } else {
                return $this->generarPdfDashboard($data);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function generarPdfDashboard($data)
    {
        $html = view('reports.dashboard', $data)->render();
        $pdf = Pdf::loadHTML($html);
        return $pdf->download('Dashboard_' . date('Y-m-d') . '.pdf');
    }

    private function generarExcelDashboard($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'DASHBOARD EJECUTIVO');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Total Ventas:');
        $sheet->setCellValue('B3', '$' . number_format($data['total_ventas'], 2));

        $sheet->setCellValue('A4', 'Total Órdenes:');
        $sheet->setCellValue('B4', $data['total_ordenes']);

        $sheet->setCellValue('A5', 'Total Usuarios:');
        $sheet->setCellValue('B5', $data['total_usuarios']);

        $sheet->setCellValue('A6', 'Generado:');
        $sheet->setCellValue('B6', $data['fecha']);

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(20);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Dashboard_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $writer->save('php://output');
        exit;
    }
}
