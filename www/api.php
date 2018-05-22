<?php

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

require_once (__DIR__ . '/../vendor/autoload.php');

$dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

AnnotationDriver::registerAnnotationClasses();

$config = new Configuration();
$config->setProxyDir(__DIR__ . '/../Proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__ . '/../docker/Hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setDefaultDB('mastermindNotes');
$config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/../Entity'));

$options = [];
$username = getenv('DATABASE_USERNAME');
if ($username) {
    $options['username'] = $username;
}
$password = getenv('DATABASE_PASSWORD');
if ($password) {
    $options['password'] = $password;
}

$host = getenv('DATABASE_HOST');
if (!$host) {
    $host = 'localhost';
}

$port = (int) getenv('DATABASE_PORT');
if (!$port) {
    $port = 27017;
}

session_start();

$authorized = (function () {
    if (!empty ($_SESSION['authorized'])) {
        return true;
    }

    if (empty ($_SERVER['PHP_AUTH_USER'])) {
        return false;
    }

    if ($_SERVER['PHP_AUTH_USER'] !== getenv('USERNAME')) {
        return false;
    }

    if ($_SERVER['PHP_AUTH_PW'] !== getenv('PASSWORD')) {
        return false;
    }

    return true;
})();

if (!$authorized) {
    //header('application/json; charset=utf8');
    header('HTTP/1.0 403 Forbidden');
    echo json_encode('Forbidden');
    exit;
}

$dm = DocumentManager::create(new Connection('mongodb://' . $host . ':' . $port, $options), $config);

$notes = $dm->createQueryBuilder(\NShiell\MastermindNotes\Entity\Note::class)
    ->getQuery()
    ->execute();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = new \NShiell\MastermindNotes\Entity\Note;
    $note->body = isset ($_POST['body']) ? $_POST['body'] : '';
    if (isset ($_POST['date'])) {
        $d1 = str_replace('00:00:00', '12:00:00', $_POST['date']);
        $d2 = str_replace('00:00:00', '13:00:00', $_POST['date']);
        $note->dateTimeStart = new \DateTime($d1);
        $note->dateTimeEnd = new \DateTime($d2);
    }
    $dm->persist($note);
    $dm->flush();
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = str_replace('/api.php/', '', $_SERVER['REQUEST_URI']);
    $note = $dm->getRepository(\NShiell\MastermindNotes\Entity\Note::class)->findOneBy(['id' => $id]);
    if (!$note) {
        header("HTTP/1.0 404 Not Found");
    } else {
        $dm->remove($note);
        $dm->flush();
    }
} else {
    echo json_encode(array_values($notes->toArray()));
}