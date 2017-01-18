<?php
/**
 * app/controllers/EntityController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
try {
    require_once '../../app/init.inc.php';

    if ($_POST['type'] === 'experiments') {
        $Entity = new Experiments($_SESSION['team_id'], $_SESSION['userid'], $_POST['id']);
    } else {
        $Entity = new Database($_SESSION['team_id'], $_SESSION['userid'], $_POST['id']);
    }

    // LOCK
    if (isset($_POST['lock'])) {
        if ($Entity->toggleLock()) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // QUICKSAVE
    if (isset($_POST['quickSave'])) {
        $title = Tools::checkTitle($_POST['title']);

        $body = Tools::checkBody($_POST['body']);

        $date = Tools::kdate($_POST['date']);

        $result = $Entity->update($title, $date, $body);

        if ($result) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // CREATE TAG
    if (isset($_POST['createTag'])) {
        $Tags = new Tags($Entity);
        $Tags->create($_POST['tag']);
    }

    // DELETE TAG
    if (isset($_POST['destroyTag'])) {
        $Tags = new Tags($Entity);
        if ($Entity->canWrite) {
            $Tags->destroy($_POST['tag_id']);
        }
    }

    // UPDATE FILE COMMENT
    if (isset($_POST['updateFileComment'])) {
        try {
            $comment = filter_var($_POST['comment'], FILTER_SANITIZE_STRING);

            if (strlen($comment) === 0 || $comment === ' ') {
                throw new Exception(_('Comment is too short'));
            }

            $id_arr = explode('_', $_POST['comment_id']);
            if (Tools::checkId($id_arr[1]) === false) {
                throw new Exception(_('The id parameter is invalid'));
            }
            $id = $id_arr[1];

            $Upload = new Uploads($Entity);
            if ($Upload->updateComment($id, $comment)) {
                echo json_encode(array(
                    'res' => true,
                    'msg' => _('Saved')
                ));
            } else {
                echo json_encode(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // CREATE UPLOAD
    if (isset($_POST['upload'])) {
        try {
            $Uploads = new Uploads($Entity);
            if ($Uploads->create($_FILES)) {
                echo json_encode(array(
                    'res' => true,
                    'msg' => _('Saved')
                ));
            } else {
                echo json_encode(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // DESTROY UPLOAD
    if (isset($_POST['uploadsDestroy'])) {
        $Uploads = new Uploads($Entity);
        if ($Uploads->destroy($_POST['upload_id'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('File deleted successfully')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }
} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
