<?php
// Configuración PDO para PostgreSQL en Neon
$host = 'ep-crimson-butterfly-b4pb78nh-pooler.c-6.us-east-2.aws.neon.tech'; 
$port = '5432';
$dbname = 'neondb';
$username = 'neondb_owner';
$password = 'npg_mN7oO4RwrcGP';

header('Content-Type: application/json');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // MÉTODO POST: Guardar documento
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documento_file'])) {
        $no_oficio = $_POST['no_oficio'];
        $fecha = $_POST['fecha'];
        $asunto = $_POST['asunto'];
        $remitente = $_POST['remitente'];
        $destinatario = $_POST['destinatario'];
        $estatus = $_POST['estatus'];
        
        // Carpeta donde se guardarán los PDFs localmente
        $directorio_subida = 'pdf/';
        if (!file_exists($directorio_subida)) {
            mkdir($directorio_subida, 0777, true);
        }
        
        $nombre_archivo = time() . '_' . basename($_FILES['documento_file']['name']);
        $ruta_final = $directorio_subida . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['documento_file']['tmp_name'], $ruta_final)) {
            $sql = "INSERT INTO documentos (no_oficio, fecha, asunto, remitente, destinatario, estatus, archivo_pdf) 
                    VALUES (:no, :fecha, :asunto, :remitente, :destinatario, :estatus, :pdf)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':no' => $no_oficio, ':fecha' => $fecha, ':asunto' => $asunto,
                ':remitente' => $remitente, ':destinatario' => $destinatario,
                ':estatus' => $estatus, ':pdf' => $nombre_archivo
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Documento registrado']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el archivo en la carpeta pdf/']);
        }
        exit;
    }
    
    // MÉTODO GET: Obtener documentos para la tabla
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query("SELECT * FROM documentos ORDER BY id_documento DESC");
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultados);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>