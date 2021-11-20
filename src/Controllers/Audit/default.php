<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\AuditLogTypes;
use MyRadio\ServiceAPI\MyRadio_AuditLog;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\ServiceAPI;

$start = !empty($_GET['auditsel-starttime']) ? strtotime($_GET['auditsel-starttime']) : time() - (86400 * 28);
$end = !empty($_GET['auditsel-endtime']) ? strtotime($_GET['auditsel-endtime']) : time();
$eventType = !empty($_GET['auditsel-eventtype']) ? $_GET['auditsel-eventtype'] : null;
$targetType = !empty($_GET['auditsel-targettype']) ? $_GET['auditsel-targettype'] : null;
$actorId = !empty($_GET['auditsel-actor']) ? $_GET['auditsel-actor'] : null;

$query = [];
if ($eventType !== null)
{
    $query['event_type'] = $eventType;
}
if ($targetType !== null)
{
    $query['target_type'] = $targetType;
}
if ($actorId !== null)
{
    $query['actor_id'] = $actorId;
}

$events = MyRadio_AuditLog::getEvents(
    $start,
    $end,
    $query
);

$typesClass = new ReflectionClass(AuditLogTypes::class);
$eventTypes = [['value' => null, 'text' => 'All']];
foreach (array_values($typesClass->getConstants()) as $constant)
{
    $eventTypes[] = ['value' => $constant, 'text' => $constant];
}

$targetTypes = [['value' => null, 'text' => 'All']];
$classRoot = dirname(__FILE__, 3) . '/Classes/ServiceAPI';
foreach (scandir($classRoot) as $fileName)
{
    if ($fileName[0] === '.')
    {
        continue;
    }
    require_once('Classes/ServiceAPI/' . $fileName);
    $class = 'MyRadio\\ServiceAPI\\' . basename($fileName, '.php');
    if (is_subclass_of($class, ServiceAPI::class))
    {
        $targetTypes[] = ['value' => $class, 'text' => (new ReflectionClass($class))->getShortName()];
    }
}

$data = [];
foreach ($events as $event)
{
    $data[] = [
        'time' => CoreUtils::happyTime($event->getEntryTime()),
        'actor' => $event->getActor()->getName(),
        'event_type' => $event->getEventType(),
        'target_type' => (new ReflectionClass($event->getTargetClass()))->getShortName(),
        'target_id' => $event->getTargetID(),
        'payload' => json_encode($event->getPayload(), JSON_PRETTY_PRINT)
    ];
}

$twig = CoreUtils::getTemplateObject()->setTemplate('Audit/view.twig')
        ->addVariable('title', 'View Audit Log')
        ->addVariable('tablescript', 'myradio.audit.view')
        ->addVariable('tabledata', $data)
        ->addVariable('starttime', $start)
        ->addVariable('endtime', $end)
        ->addVariable('actor', $actorId)
        ->addVariable('eventType', $eventType)
        ->addVariable('targetType', $targetType)
        ->addVariable('eventTypes', $eventTypes)
        ->addVariable('targetTypes', $targetTypes);

if ($actor !== null)
{
    $twig = $twig->addVariable('actorOptions', [ 'membername' => MyRadio_User::getInstance($actor)->getName() ]);
}

$twig->render();
