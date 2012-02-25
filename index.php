<?php
/**
 * Change blindness test.
 * Visual Communication Design. Assignment 1.
 *
 * @author Raymond Jelierse <r.jelierse@student.tudelft.nl>
 *
 * This work is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-nc-sa/3.0/ or send a letter to
 * Creative Commons, 444 Castro Street, Suite 900, Mountain View, California, 94041, USA.
 */

require_once 'Twig/Autoloader.php';
Twig_Autoloader::register();

session_start();

$base_dir = dirname(__FILE__);

$settings = json_decode(file_get_contents('settings.json'));

$db = new mysqli($settings->database->host, $settings->database->username, $settings->database->password, $settings->database->name);

$twig_loader = new Twig_Loader_Filesystem($base_dir.'/views');
$twig = new Twig_Environment($twig_loader, array('debug' => true));

$variables = array(
    'baseURL' => $settings->baseURL
);

$template = false;
$mode = !empty($_GET['mode']) ? $_GET['mode'] : 'start';
$phase = !empty($_GET['phase']) && in_array($_GET['phase'], $settings->phases) ? $_GET['phase'] : false;
$index = 0;

if (!empty($phase)) {
    $index = array_search($phase, $settings->phases, true) + 1;
}

// Store the results upon submittal.
if (!empty($phase) && isset($_POST['responsetime'])) {
    $_SESSION['results'][$phase] = array(
        'xcoordinate' => $_POST['xcoordinate'],
        'ycoordinate' => $_POST['ycoordinate'],
        'responsetime' => $_POST['responsetime']
    );

    // End of the line.
    if ($index == count($settings->phases)) {
        header('HTTP/1.0 302 Found');
        header('Location: http://vcd.sparse.nl/finish');
        exit;
    }
    // Next phase.
    else {
        $mode = 'phase_next';
    }
}

switch ($mode) {
    case 'start':
        $template = 'index.html.twig';
        break;
    case 'phase':
        $template = 'change.html.twig';
        $variables['imageWithElement'] = 'img/'.$phase.'-with.png';
        $variables['imageWithoutElement'] = 'img/'.$phase.'-without.png';
        $variables['step_count'] = $index;
        break;
    case 'phase_next':
        $template = 'next.html.twig';
        $variables['next_url'] = 'phase/'.$settings->phases[$index];
        $variables['step_count'] = $index;
        break;
    case 'finish':
        $template = 'finish.html.twig';
        // Store the result.
        $query = $db->prepare('insert into vcd_results values (null, unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $query->bind_param(
            'siiiiiiiii',
            $_SERVER['REMOTE_ADDR'],
            $_SESSION['results']['notable']['xcoordinate'],
            $_SESSION['results']['notable']['ycoordinate'],
            $_SESSION['results']['notable']['responsetime'],
            $_SESSION['results']['unnoted']['xcoordinate'],
            $_SESSION['results']['unnoted']['ycoordinate'],
            $_SESSION['results']['unnoted']['responsetime'],
            $_SESSION['results']['renoted']['xcoordinate'],
            $_SESSION['results']['renoted']['ycoordinate'],
            $_SESSION['results']['renoted']['responsetime']
        ) or die('Could not prepare query');
        $query->execute() or die('Could not execute query');
        $query->close();
        break;
    case 'results':
        $download = isset($_GET['download']);

        if ($settings->debug) {
            $variables['allow_filtering'] = true;
            $filtered = isset($_GET['filtered']) || $download;
        }
        else {
            $filtered = true;
        }

        if ($download) {
            header('Content-Type: text/tab-separated-values');
            header('Content-Disposition: attachment; filename=vcd-results.txt');
            $template = 'results.txt.twig';
        }
        else {
            $template = 'results.html.twig';
        }

        if ($filtered) {
            $variables['filtered'] = true;
            $results = $db->query("select * from vcd_results where result_host != '192.168.0.1' order by result_date asc");
        }
        else {
            $results = $db->query("select * from vcd_results order by result_date asc");
        }

        if ($results === false) {
            header('HTTP/1.0 500 Internal Server Error');
            exit;
        }

        $variables['records'] = $results->num_rows;

        $variables['data'] = array();
        $variables['stats'] = array();

        while (($record = $results->fetch_assoc()) !== null) {
            $data = array();
            $stats = array();

            // Prepare data
            foreach ($record as $name => $value) {
                list($cat, $key) = explode('_', $name);
                $data[$cat][$key] = $value;
            }
            $variables['data'][] = $data;

            // Build statistics
            foreach ($settings->phases as $phase) {
                $stats[$phase] = array(
                    'correct' => (($data[$phase]['xcoordinate'] >= $settings->elementLocations->{$phase}->topleft->x)
                               && ($data[$phase]['xcoordinate'] <= $settings->elementLocations->{$phase}->bottomright->x)
                               && ($data[$phase]['ycoordinate'] >= $settings->elementLocations->{$phase}->topleft->y)
                               && ($data[$phase]['ycoordinate'] <= $settings->elementLocations->{$phase}->bottomright->y)),
                    'time' => $data[$phase]['responsetime']
                );
            }
            $variables['stats'][] = $stats;
        }

        $results->free();
        break;
}

if (!empty ($template)) {
    echo $twig->render($template, $variables);
}
else {
    header('HTTP/1.0 404 Not Found');
}