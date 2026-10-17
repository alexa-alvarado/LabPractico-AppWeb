<?php
// Evitar que errores HTML rompan la respuesta JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

$host = 'ep-crimson-butterfly-b4pb78nh-pooler.c-6.us-east-2.aws.neon.tech'; 
$port = '5432';
$dbname = 'neondb';
$username = 'neondb_owner';
$password = 'npg_mN7oO4RWrcGP';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // MÉTODO POST: Guardar documento
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $no_oficio = $_POST['no_oficio'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $asunto = $_POST['asunto'] ?? '';
        $remitente = $_POST['remitente'] ?? '';
        $destinatario = $_POST['destinatario'] ?? '';
        $estatus = $_POST['estatus'] ?? '';
        
        $nombre_archivo = "Documento_adjunto.pdf"; // Nombre de seguridad si Vercel bloquea el archivo
        
        // Intentamos procesar el archivo físico (funcionará en XAMPP, fallará en Vercel de forma silenciosa)
        if (isset($_FILES['documento_file']) && $_FILES['documento_file']['error'] === UPLOAD_ERR_OK) {
            $nombre_original = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['documento_file']['name']));
            $nombre_archivo = time() . '_' . $nombre_original;
            @mkdir('pdf/', 0777, true);
            @move_uploaded_file($_FILES['documento_file']['tmp_name'], 'pdf/' . $nombre_archivo);
        }
        
        $sql = "INSERT INTO documentos (no_oficio, fecha, asunto, remitente, destinatario, estatus, archivo_pdf) 
                VALUES (:no, :fecha, :asunto, :remitente, :destinatario, :estatus, :pdf)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':no' => $no_oficio, ':fecha' => $fecha, ':asunto' => $asunto,
            ':remitente' => $remitente, ':destinatario' => $destinatario,
            ':estatus' => $estatus, ':pdf' => $nombre_archivo
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'Documento registrado con éxito']);
        exit;
    }
    
    // MÉTODO GET: Obtener documentos
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query("SELECT * FROM documentos ORDER BY id_documento DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Petición no válida']);

} catch (Throwable $e) {
    // Captura cualquier error crítico (conexión, permisos, drivers) y lo muestra
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error real del servidor: ' . $e->getMessage()]);
}
?>