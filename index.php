<?php
$carpetaNombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$carpetaRuta = "./descarga/" . $carpetaNombre;

try {
    if (!file_exists($carpetaRuta)) {
        mkdir($carpetaRuta, 0755, true);
        $mensaje = "Carpeta '$carpetaNombre' creada con éxito.";
    } else {
        $mensaje = "La carpeta '$carpetaNombre' ya existe.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['archivo'])) {
            $archivos = $_FILES['archivo'];

            foreach ($archivos['name'] as $index => $archivoNombre) {
                if ($archivos['error'][$index] !== UPLOAD_ERR_OK) {
                    throw new Exception("Error al subir el archivo: " . $archivos['error'][$index]);
                }

                $archivoTmpName = $archivos['tmp_name'][$index];
                $archivoDestino = $carpetaRuta . '/' . $archivoNombre;
                if (move_uploaded_file($archivoTmpName, $archivoDestino)) {
                    $mensaje = "Archivo '$archivoNombre' subido con éxito.";
                } else {
                    throw new Exception("Error al mover el archivo '$archivoNombre' al destino.");
                }
            }
        }

        if (isset($_POST['eliminarArchivo'])) {
            $archivoAEliminar = $_POST['eliminarArchivo'];
            $archivoRutaAEliminar = $carpetaRuta . '/' . $archivoAEliminar;

            if (file_exists($archivoRutaAEliminar)) {
                if (unlink($archivoRutaAEliminar)) {
                    $mensaje = "Archivo '$archivoAEliminar' eliminado con éxito.";
                } else {
                    throw new Exception("Error al eliminar el archivo.");
                }
            } else {
                throw new Exception("El archivo '$archivoAEliminar' no existe.");
            }
        }

        if (isset($_POST['eliminarTodos'])) {
            $files = scandir($carpetaRuta);
            $files = array_diff($files, array('.', '..'));
            foreach ($files as $file) {
                unlink($carpetaRuta . '/' . $file);
            }
            $mensaje = "Todos los archivos han sido eliminados.";
        }

        if (isset($_POST['descargarTodos'])) {
            $zip = new ZipArchive();
            $zipFileName = $carpetaNombre . ".zip";
            if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
                $files = scandir($carpetaRuta);
                $files = array_diff($files, array('.', '..'));
                foreach ($files as $file) {
                    $zip->addFile($carpetaRuta . '/' . $file, $file);
                }
                $zip->close();
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                readfile($zipFileName);
                unlink($zipFileName);
                exit;
            } else {
                $mensaje = "No se pudo crear el archivo ZIP.";
            }
        }

        if (isset($_POST['descargarProyecto'])) {
            $zip = new ZipArchive();
            $zipFileName = 'proyecto_completo.zip';

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $rootPath = realpath('./'); 

                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {

                        $filePath = $file->getRealPath();                      
                        $relativePath = substr($filePath, strlen($rootPath) + 1);                     
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                header('Content-Length: ' . filesize($zipFileName));
                readfile($zipFileName);
                unlink($zipFileName);
                exit;
            } else {
                echo "Error al crear el archivo ZIP.";
            }
        }
    }
} catch (Exception $e) {
    $mensaje = "Error: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compartir Archivos</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Compartir archivos <sup class="beta">BETA</sup></h1>
    <div class="content">
        <h3>Sube tus archivos y comparte este enlace temporal: <span>ibu.pe/?nombre=fe5<?php echo htmlspecialchars($carpetaNombre); ?></span></h3>
        <div class="container">
            <div class="drop-area" id="drop-area">
                <form action="" id="form" method="POST" enctype="multipart/form-data">
                    <input type="file" class="hidden-upload-btn" name="archivo[]" id="archivo" multiple>
                    <label for="archivo" class="upload-label" id="uploadLabel">
                        <div class="upload-content">
                            <span id="uploadText">Arrastra tus archivos aquí</span><br>
                            <span>o</span><br>
                            <b>Sube Archivos</b>
                        </div>
                        <div class="loader hidden"></div>
                    </label>
                </form>
            </div>
            <div class="container2">
                <div id="file-list" class="pila">
                    <?php
                    $targetDir = $carpetaRuta;
                    $files = scandir($targetDir);
                    $files = array_diff($files, array('.', '..'));
                    if (count($files) > 0) {
                        echo "<h3 style='margin-bottom:10px;'>Archivos Subidos:</h3>";
                        foreach ($files as $file) {
                            echo "<div class='archivos_subidos'>
                            <div><a href='$carpetaRuta/$file' download class='boton-descargar'>$file</a></div>
                            <div>
                            <form action='' method='POST' style='display:inline;'>
                                <input type='hidden' name='eliminarArchivo' value='$file'>
                                <button type='submit' class='btn_delete'>
                                    <svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-trash' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='none' stroke-linecap='round' stroke-linejoin='round'>
                                        <path stroke='none' d='M0 0h24v24H0z' fill='none'/>
                                        <path d='M4 7l16 0' />
                                        <path d='M10 11l0 6' />
                                        <path d='M14 11l0 6' />
                                        <path d='M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12' />
                                        <path d='M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3' />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        </div>";
                        }
                    } else {
                        echo "No se han subido archivos.";
                    }
                    ?>
                </div>
                <form action="" method="POST" style="display:inline;">
                    <button type="submit" name="eliminarTodos" class="btn_delete_all">Eliminar Todos</button>
                </form>
                <form action="" method="POST" style="display:inline;">
                    <button type="submit" name="descargarTodos" class="btn_download_all">Descargar Todo</button>
                </form>
            </div>
        </div>

        <!-- Botón para Descargar Todo el Proyecto -->
        <form action="" method="POST" style="margin-top: 20px;">
            <button type="submit" name="descargarProyecto" class="btn_download_project">Descargar Proyecto Completo</button>
        </form>

        <?php if (isset($mensaje)): ?>
            <p><?php echo $mensaje; ?></p>
        <?php endif; ?>
    </div>
    <script>
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('archivo');
        const uploadLabel = document.getElementById('uploadLabel');
        const uploadText = document.getElementById('uploadText');
        const loader = document.querySelector('.loader');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
        });
        dropArea.addEventListener('drop', handleDrop, false);
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            document.getElementById('form').submit();
        }
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                uploadText.textContent = 'Archivos en proceso...';
                loader.classList.remove('hidden');
                uploadLabel.classList.add('uploading');
                document.getElementById('form').submit();
            }
        });
    </script>
</body>
</html>
