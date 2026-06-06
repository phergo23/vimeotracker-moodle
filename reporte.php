// ==========================================
// ⚙️ CONFIGURACIÓN DE SEGURIDAD Y AZURE
// ==========================================
$token_seguro = 'TOKEN_SEGURO_AQUI'; 

// !!! REEMPLAZA ESTOS DATOS CON TUS CREDENCIALES DE AZURE !!!
$tenant_id     = 'TU_TENANT_ID_AQUI'; 
$client_id     = 'TU_CLIENT_ID_AQUI'; 
$client_secret = 'TU_CLIENT_SECRET_AQUI'; 
$correo_emisor = 'no-reply@mail.com'; 
// ==========================================

$log_file = __DIR__ . '/azure_debug.log';

function guardar_log($mensaje) {
    global $log_file;
    $fecha = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$fecha] $mensaje\n", FILE_APPEND);
}

$courseid  = optional_param('id', 0, PARAM_INT);
$formato   = optional_param('formato', 'pdf', PARAM_ALPHANUM); 
$token_url = optional_param('token', '', PARAM_RAW);
$email_url = optional_param('email', '', PARAM_RAW); 

if ($token_url !== $token_seguro || empty($courseid)) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(array('status' => 'error', 'message' => 'Acceso denegado.'));
    die();
}

$course = $DB->get_record('course', array('id' => $courseid));
if (!$course) {
    echo json_encode(array('status' => 'error', 'message' => 'Curso no encontrado.'));
    die();
}

// 1. Extraer alumnos matriculados
$sql_students = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email 
                 FROM {user} u
                 JOIN {user_enrolments} ue ON ue.userid = u.id
                 JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = ? AND u.deleted = 0
                 ORDER BY u.lastname ASC, u.firstname ASC";
$students = $DB->get_records_sql($sql_students, array($courseid));

// 2. Obtener videos interactivos del curso
$videos_db = $DB->get_records_sql(
    "SELECT DISTINCT vimeo_id FROM {filter_vimeotracker_time} WHERE courseid = ?", 
    array($courseid)
);

// 3. MOTOR HÍBRIDO DE BÚSQUEDA DE TÍTULOS
$nombres_videos = array();
$capitulos_libros = $DB->get_records_sql(
    "SELECT bc.id, bc.title, bc.content FROM {book_chapters} bc JOIN {book} b ON b.id = bc.bookid WHERE b.course = ?", 
    array($courseid)
);
$paginas_moodle = $DB->get_records('page', array('course' => $courseid), '', 'id, name, content, intro');
$etiquetas_moodle = $DB->get_records('label', array('course' => $courseid), '', 'id, intro');

foreach ($videos_db as $v) {
    $vimeo_id = (string)$v->vimeo_id;
    $nombres_videos[$vimeo_id] = "Video ID: " . $vimeo_id; 
    $encontrado = false;

    if ($capitulos_libros) {
        foreach ($capitulos_libros as $ch) {
            if (strpos($ch->content, $vimeo_id) !== false) {
                $nombres_videos[$vimeo_id] = $ch->title;
                $encontrado = true;
                break;
            }
        }
    }
    if (!$encontrado && $paginas_moodle) {
        foreach ($paginas_moodle as $p) {
            if (strpos($p->content, $vimeo_id) !== false || strpos($p->intro, $vimeo_id) !== false) {
                $nombres_videos[$vimeo_id] = $p->name;
                $encontrado = true;
                break;
            }
        }
    }
    if (!$encontrado && $etiquetas_moodle) {
        foreach ($etiquetas_moodle as $l) {
            if (strpos($l->intro, $vimeo_id) !== false) {
                $texto_limpio = trim(html_to_text($l->intro));
                if (!empty($texto_limpio)) {
                    $nombres_videos[$vimeo_id] = core_text::substr($texto_limpio, 0, 40) . "...";
                }
                $encontrado = true;
                break;
            }
        }
    }
}

// --- GENERACIÓN DEL CONTENIDO EXCEL / HTML ---
$meta_utf8 = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

if ($formato === 'excel') {
    $mimetype = 'application/vnd.ms-excel';
    $attachment_name = 'Reporte_Curso_' . $courseid . '.xls';

    $output = "<html><head>" . $meta_utf8 . "<style>table{font-family:Arial;border-collapse:collapse;}th{background-color:#1a365d;color:white;padding:6px;font-weight:bold;}td{border:1px solid #ccc;padding:6px;}</style></head><body>";
    $output .= "<table border='1'>";
    $output .= "<tr><th colspan='2' style='background-color:#1a365d; color:white;'>REPORTE EJECUTIVO DE VIDEOS</th><th colspan='" . (count($videos_db) ? count($videos_db) : 1) . "' style='background-color:#1a365d; color:white;'>Curso: " . htmlspecialchars($course->fullname, ENT_QUOTES, 'UTF-8') . "</th></tr>";
    $output .= "<tr><th style='background-color:#2d3748; color:white;'>Nombre del Alumno</th><th style='background-color:#2d3748; color:white;'>Correo</th>";

    foreach ($videos_db as $v) {
        $output .= "<th style='background-color:#2d3748; color:white;'>" . htmlspecialchars($nombres_videos[$v->vimeo_id], ENT_QUOTES, 'UTF-8') . "</th>";
    }
    $output .= "</tr>";

    foreach ($students as $student) {
        $nombre_completo = $student->lastname . ", " . $student->firstname;
        $output .= "<tr><td><strong>" . htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8') . "</strong></td><td>" . htmlspecialchars($student->email, ENT_QUOTES, 'UTF-8') . "</td>";
        foreach ($videos_db as $v) {
            $progress = $DB->get_record('filter_vimeotracker_time', array('userid' => $student->id, 'vimeo_id' => $v->vimeo_id, 'courseid' => $courseid));
            if ($progress) {
                $segundos = floor($progress->last_position);
                $output .= "<td style='background-color:#c6f6d5; text-align:center;'>" . floor($segundos/60) . "m " . ($segundos%60) . "s</td>";
            } else {
                $output .= "<td style='color:#a0aec0; text-align:center;'>0s (Sin ver)</td>";
            }
        }
        $output .= "</tr>";
    }
    $output .= "</table></body></html>";

} else {
    $mimetype = 'text/html';
    $attachment_name = 'Reporte_Curso_' . $courseid . '.html';

    $output = '<html><head>' . $meta_utf8 . '<style>body{font-family:Arial;color:#2d3748;padding:20px;}.header{background-color:#1a365d;color:white;padding:15px;border-radius:4px;}.meta-box{margin:15px 0;border:1px solid #e2e8f0;padding:10px;background-color:#edf2f7;}.data-table{width:100%;border-collapse:collapse;}.data-table th{background-color:#2d3748;color:white;padding:8px;border:1px solid #cbd5e0;font-weight:bold;}.data-table td{padding:8px;border:1px solid #e2e8f0;}</style></head><body>';
    $output .= '<div class="header"><h1>Reporte Ejecutivo de Videos</h1><p>Moodle Automation System</p></div>';
    $output .= '<div class="meta-box"><strong>Curso:</strong> '.htmlspecialchars($course->fullname, ENT_QUOTES, 'UTF-8').'<br><strong>Fecha de Generación:</strong> '.date('d/m/Y H:i').'</div>';
    $output .= '<table class="data-table"><thead><tr><th>Nombre del Alumno</th><th>Correo Electrónico</th>';
    foreach ($videos_db as $v) { 
        $output .= '<th>'.htmlspecialchars($nombres_videos[$v->vimeo_id], ENT_QUOTES, 'UTF-8').'</th>'; 
    }
    $output .= '</tr></thead><tbody>';

    foreach ($students as $student) {
        $nombre_completo = $student->lastname . " " . $student->firstname;
        $output .= '<tr><td><strong>'.htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8').'</strong></td><td>'.htmlspecialchars($student->email, ENT_QUOTES, 'UTF-8').'</td>';
        foreach ($videos_db as $v) {
            $progress = $DB->get_record('filter_vimeotracker_time', array('userid' => $student->id, 'vimeo_id' => $v->vimeo_id, 'courseid' => $courseid));
            if ($progress) {
                $segundos = floor($progress->last_position);
                $output .= '<td style="background-color:#e6f4ea; text-align:center;">'.floor($segundos/60).'m '.($segundos%60).'s</td>';
            } else {
                $output .= '<td style="color:#a0aec0; text-align:center; background-color:#f7fafc;">0s (Sin ver)</td>';
            }
        }
        $output .= '</tr>';
// ==========================================
// ⚙️ CONFIGURACIÓN DE SEGURIDAD Y AZURE
// ==========================================
$token_seguro = 'IT_Soporte_CostaRica_2026'; 
$token_seguro = 'TOKEN_SEGURO_AQUI'; 

// !!! REEMPLAZA ESTOS DATOS CON TUS CREDENCIALES DE AZURE !!!
$tenant_id     = 'TU_TENANT_ID_AQUI'; 
$client_id     = 'TU_CLIENT_ID_AQUI'; 
$client_secret = 'TU_CLIENT_SECRET_AQUI'; 
$correo_emisor = 'no-reply@mail.com'; 
// ==========================================

$log_file = __DIR__ . '/azure_debug.log';

function guardar_log($mensaje) {
    global $log_file;
    $fecha = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$fecha] $mensaje\n", FILE_APPEND);
}

$courseid  = optional_param('id', 0, PARAM_INT);
$formato   = optional_param('formato', 'pdf', PARAM_ALPHANUM); 
$token_url = optional_param('token', '', PARAM_RAW);
$email_url = optional_param('email', '', PARAM_RAW); 

if ($token_url !== $token_seguro || empty($courseid)) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(array('status' => 'error', 'message' => 'Acceso denegado.'));
    die();
}

$course = $DB->get_record('course', array('id' => $courseid));
if (!$course) {
    echo json_encode(array('status' => 'error', 'message' => 'Curso no encontrado.'));
    die();
}

// 1. Extraer alumnos matriculados
$sql_students = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email 
                 FROM {user} u
                 JOIN {user_enrolments} ue ON ue.userid = u.id
                 JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = ? AND u.deleted = 0
                 ORDER BY u.lastname ASC, u.firstname ASC";
$students = $DB->get_records_sql($sql_students, array($courseid));

// 2. Obtener videos interactivos del curso
$videos_db = $DB->get_records_sql(
    "SELECT DISTINCT vimeo_id FROM {filter_vimeotracker_time} WHERE courseid = ?", 
    array($courseid)
);

// 3. MOTOR HÍBRIDO DE BÚSQUEDA DE TÍTULOS
$nombres_videos = array();
$capitulos_libros = $DB->get_records_sql(
    "SELECT bc.id, bc.title, bc.content FROM {book_chapters} bc JOIN {book} b ON b.id = bc.bookid WHERE b.course = ?", 
    array($courseid)
);
$paginas_moodle = $DB->get_records('page', array('course' => $courseid), '', 'id, name, content, intro');
$etiquetas_moodle = $DB->get_records('label', array('course' => $courseid), '', 'id, intro');

foreach ($videos_db as $v) {
    $vimeo_id = (string)$v->vimeo_id;
    $nombres_videos[$vimeo_id] = "Video ID: " . $vimeo_id; 
    $encontrado = false;

    if ($capitulos_libros) {
        foreach ($capitulos_libros as $ch) {
            if (strpos($ch->content, $vimeo_id) !== false) {
                $nombres_videos[$vimeo_id] = $ch->title;
                $encontrado = true;
                break;
            }
        }
    }
    if (!$encontrado && $paginas_moodle) {
        foreach ($paginas_moodle as $p) {
            if (strpos($p->content, $vimeo_id) !== false || strpos($p->intro, $vimeo_id) !== false) {
                $nombres_videos[$vimeo_id] = $p->name;
                $encontrado = true;
                break;
            }
        }
    }
    if (!$encontrado && $etiquetas_moodle) {
        foreach ($etiquetas_moodle as $l) {
            if (strpos($l->intro, $vimeo_id) !== false) {
                $texto_limpio = trim(html_to_text($l->intro));
                if (!empty($texto_limpio)) {
                    $nombres_videos[$vimeo_id] = core_text::substr($texto_limpio, 0, 40) . "...";
                }
                $encontrado = true;
                break;
            }
        }
    }
}

// --- GENERACIÓN DEL CONTENIDO EXCEL / HTML ---
$meta_utf8 = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

if ($formato === 'excel') {
    $mimetype = 'application/vnd.ms-excel';
    $attachment_name = 'Reporte_Curso_' . $courseid . '.xls';

    $output = "<html><head>" . $meta_utf8 . "<style>table{font-family:Arial;border-collapse:collapse;}th{background-color:#1a365d;color:white;padding:6px;font-weight:bold;}td{border:1px solid #ccc;padding:6px;}</style></head><body>";
    $output .= "<table border='1'>";
    $output .= "<tr><th colspan='2' style='background-color:#1a365d; color:white;'>REPORTE EJECUTIVO DE VIDEOS</th><th colspan='" . (count($videos_db) ? count($videos_db) : 1) . "' style='background-color:#1a365d; color:white;'>Curso: " . htmlspecialchars($course->fullname, ENT_QUOTES, 'UTF-8') . "</th></tr>";
    $output .= "<tr><th style='background-color:#2d3748; color:white;'>Nombre del Alumno</th><th style='background-color:#2d3748; color:white;'>Correo</th>";

    foreach ($videos_db as $v) {
        $output .= "<th style='background-color:#2d3748; color:white;'>" . htmlspecialchars($nombres_videos[$v->vimeo_id], ENT_QUOTES, 'UTF-8') . "</th>";
    }
    $output .= "</tr>";

    foreach ($students as $student) {
        $nombre_completo = $student->lastname . ", " . $student->firstname;
        $output .= "<tr><td><strong>" . htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8') . "</strong></td><td>" . htmlspecialchars($student->email, ENT_QUOTES, 'UTF-8') . "</td>";
        foreach ($videos_db as $v) {
            $progress = $DB->get_record('filter_vimeotracker_time', array('userid' => $student->id, 'vimeo_id' => $v->vimeo_id, 'courseid' => $courseid));
            if ($progress) {
                $segundos = floor($progress->last_position);
                $output .= "<td style='background-color:#c6f6d5; text-align:center;'>" . floor($segundos/60) . "m " . ($segundos%60) . "s</td>";
            } else {
                $output .= "<td style='color:#a0aec0; text-align:center;'>0s (Sin ver)</td>";
            }
