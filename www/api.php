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
if (empty ($_SESSION['authorized'])) {
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
    $note->body = $_POST['body'];
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
    //var_dump(str_replace('/api.php/', '', $_SERVER['REQUEST_URI']));die('=-=-=');
} else {
    echo json_encode(array_values($notes->toArray()));
}