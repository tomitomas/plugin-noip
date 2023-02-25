<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div id='modal_alert'></div>

<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active">
        <legend><i class="fas fa-camera"></i> {{Screenshot du dernier scan}}</legend>
        <div>
            <?php
            $allFiles = glob('plugins/noip/data/' . '*');
            // log::add('noip', 'debug', 'all files ===> ' . json_encode($allFiles));
            $outputFilename = 'plugins/noip/data/output.json';
            if (file_exists($outputFilename)) {
                echo "<a href='" . $outputFilename . "' target='_blank'><span style='color:blue'>Résultat : " . basename($outputFilename) . "</span></a>";
                echo "<br>";
            }

            foreach ($allFiles as $filename) {

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                switch ($ext) {
                    case 'png':
                    case 'jpg':
                    case 'jpeg':
                        echo "<img src='" . $filename . "' alt='" . $filename . "' width='50%'/>";
                        echo "<br/>";
                        break;

                    case 'json':
                    case 'txt':
                        //do nothing
                        break;

                    default:
                        echo $filename;
                        echo "<br/>";
                        break;
                }
            }
            ?>
        </div>
    </div>
</div>