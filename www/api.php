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

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $uriParts = explode('/', $_SERVER['REQUEST_URI']);
    $id = array_pop($uriParts);

    $noteRepo = $dm->getRepository(\NShiell\MastermindNotes\Entity\Note::class);
    $note = $noteRepo->find($id);
    $dm->remove($note);
    $dm->flush();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uriParts = explode('/', $_SERVER['REQUEST_URI']);
    $id = array_pop($uriParts);
    $idBefore = array_pop($uriParts);

    $noteRepo = $dm->getRepository(\NShiell\MastermindNotes\Entity\Note::class);
    if ($id == 'notes' || ($id == '' && $idBefore == 'notes')) {
        $note = new \NShiell\MastermindNotes\Entity\Note;
    } else {
        $note = $noteRepo->find($id);
    }

    if (!$note) {
        die (json_encode(['error' => 'bad id']));
    }

    $note->body = isset ($_POST['body']) ? $_POST['body'] : '';
    // "2008-07-01T22:35:17.02"

    /** @todo make better validation */
    if ($_POST['dateTimeStart']) {
        if (!\DateTime::createFromFormat('Y-m-d\TH:i:s', $_POST['dateTimeStart'])) {
            die (json_encode(['error' => 'dateTimeStart']));
        }
        $note->dateTimeStart = new \DateTime(
            $_POST['dateTimeStart'],
            new \DateTimeZone('Europe/London')
        );
    }

    if ($_POST['dateTimeEnd']) {
        if (!\DateTime::createFromFormat('Y-m-d\TH:i:s', $_POST['dateTimeEnd'])) {
            die (json_encode(['error' => 'dateTimeEnd']));
        }
        $note->dateTimeEnd = new \DateTime(
            $_POST['dateTimeEnd'],
            new \DateTimeZone('Europe/London'));
    }

    if ($note->dateTimeEnd < $note->dateTimeStart) {
        $note->dateTimeEnd = $note->dateTimeStart;
    }

    $dm->persist($note);
    $dm->flush();
    echo json_encode($note);
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