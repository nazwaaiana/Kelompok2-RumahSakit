<?php
require_once "../dbcontroller.php";
$db = new dbcontroller();

if (isset($_POST['id_ruangan'])) {
    $id_ruangan = intval($_POST['id_ruangan']);
    
    $sql_beds = "SELECT 
                    t.*,
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM t_rawatinap ri 
                            WHERE ri.f_idbed = t.f_idbed 
                            AND ri.f_waktukeluar IS NULL
                        ) THEN 'Terisi'
                        ELSE 'Tersedia'
                    END as status_bed
                 FROM t_tempattidur t
                 WHERE t.f_idruangan = $id_ruangan
                 AND t.f_stsfisik = 'Aktif'
                 ORDER BY t.f_nomorbed ASC";
    
    $beds = $db->getALL($sql_beds);
    
    if (empty($beds)) {
        echo '<div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Tidak ada tempat tidur tersedia di ruangan ini.
                </div>
              </div>';
    } else {
        foreach ($beds as $bed) {
            $status_class = $bed['status_bed'] == 'Tersedia' ? 'bed-available' : 'bed-occupied';
            $disabled = $bed['status_bed'] == 'Tersedia' ? '' : 'disabled';
            $icon = $bed['status_bed'] == 'Tersedia' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
            
            echo '<div class="col-md-3 col-sm-4 col-6 mb-3">
                    <div class="bed-item ' . $status_class . ' p-3 rounded text-center" 
                         data-idbed="' . $bed['f_idbed'] . '" ' . $disabled . '>
                        <i class="fas fa-bed fa-2x mb-2"></i>
                        <h6 class="mb-1">' . htmlspecialchars($bed['f_nomorbed']) . '</h6>
                        <small><i class="fas ' . $icon . '"></i> ' . $bed['status_bed'] . '</small>
                    </div>
                  </div>';
        }
    }
}
?>