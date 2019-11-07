<?php

namespace NShiell\MastermindNotes\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\CalDAV\Backend\AbstractBackend;
use Doctrine\Common\Persistence\ObjectRepository;
use NShiell\MastermindNotes\Entity\Note;

use Sabre\VObject\Reader;

/**
 * Mastermind Notes CalDAV backend.
 * To make this class work, you absolutely need to have the PropertyStorage
 * plugin enabled
 * @todo refactor
 */
class MastermindNotes extends AbstractBackend
{
    const SEPERATOR = '--------';

    /** @var ObjectRepository */
    private $noteRepo;

    /** @var string */
    private $username;

    private $persistance;

    public function __construct(ObjectRepository $noteRepo,
                                $persistance,
                                string $username)
    {
        $this->noteRepo = $noteRepo;
        $this->username = $username;
        $this->persistance = $persistance;
    }

    /** @todo refctor */
    private function getIcsIdForNote($note)
    {
        return $note->icsId ?? $note->id;
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the calendar.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     * @return array
     */
    function getCalendarsForUser($principalUri) {
        return [
            [// http://localhost:8080/calendars/$username/mastermind-notes/
                'id' => 1,
                'uri' => 'mastermind-notes',
                'principaluri' => 'principals/' . $this->username,
                '{DAV:}displayname' => 'Mastermind Notes',
                '{http://apple.com/ns/ical/}calendar-color' => '#FFDD77']
        ];
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used
     * to reference this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string
     */
    function createCalendar($principalUri, $calendarUri, array $properties) {
        throw new \Exception(__CLASS__ . '->' . __METHOD__ . 'Not implentted');
    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param string $calendarId
     * @return void
     */
    function deleteCalendar($calendarId) {
        throw new \Exception(__CLASS__ . '->' . __METHOD__ . 'Not implentted');
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param string $calendarId
     * @return array
     */
    function getCalendarObjects($calendarId) {
        if ($calendarId != 1) {
            return [];
        }

        $events = [];
        foreach ($this->noteRepo->findAll() as $note) {
            $events[] = $this->noteToCalendar($note);
        }
        
        return $events;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return array|null
     */
    function getCalendarObject($calendarId, $objectUri) {
        if ($calendarId != 1) {
            return null;
        }

        $note = $this->findOneByUri($objectUri);

        if ($note) {
            return $this->noteToCalendar($note);
        }
    }

    private function findOneByUri(string $objectUri): ?Note
    {
        $uriPars = explode('.', $objectUri);
        return $this->noteRepo->findOneBy(['id' => $uriPars[0]])
            ?? $this->noteRepo->findOneBy(['icsUri' => $objectUri]);
    }

    private function noteToCalendar(Note $note): ?array {
        $veventData = [
            'UID'     => $this->getIcsIdForNote($note),
            'DTSTART' => $note->dateTimeStart ?? new \DateTime,
            'DTEND'   => $note->dateTimeEnd ?? new \DateTime
        ];

        if ($note->body) {
            if (strpos($note->body, self::SEPERATOR) !== false) {
                $bodyParts = explode(self::SEPERATOR, $note->body);
                if (count($bodyParts) > 1) {
                    $veventData['SUMMARY'] = trim($bodyParts[0]);
                    $veventData['DESCRIPTION'] = trim($bodyParts[1]);
                }
            }

            if (!isset ($veventData['SUMMARY'])) {
                $bodyLines = explode(PHP_EOL, trim($note->body));
                $veventData['SUMMARY'] = substr($bodyLines[0], 0, 50);
                $veventData['DESCRIPTION'] = $note->body;
            }
        }

        $vcalendar = new \Sabre\VObject\Component\VCalendar([
            'VEVENT' => $veventData
        ]);

        $event = $vcalendar->serialize();
        return [
            'id'           => $note->id,
            'uri'          => $note->icsUri ?? $note->id . '.ics',
            'etag'         => '"' . md5($note->id . '|' . $event) . '"',
            'calendarid'   => 1,
            'size'         => strlen($event),
            'calendardata' => $event
        ];
    }

    private function populateNoteFromCalendarData(Note $note,
                                                  string $calendarData,
                                                  string $objectUri = null)
    {
        $vcalendar = Reader::read($calendarData, Reader::OPTION_FORGIVING);

        if (!$vcalendar->VEVENT) {
            throw new \DomainException('No VEVENT');
        }

        $note->icsId = $vcalendar->VEVENT->UID->getParts()[0];
        $note->icsUri = $objectUri;
        $bodyParts = [];
        if ($vcalendar->VEVENT->SUMMARY) {
            if ($vcalendar->VEVENT->SUMMARY->getParts()) {
                if (is_array($vcalendar->VEVENT->SUMMARY->getParts())) {
                    if (isset ($vcalendar->VEVENT->SUMMARY->getParts()[0])) {
                        $bodyParts[] = $vcalendar->VEVENT->SUMMARY->getParts()[0];
                    }
                }
            }
        }

        if ($vcalendar->VEVENT->DESCRIPTION) {
            if ($vcalendar->VEVENT->DESCRIPTION->getParts()) {
                if (is_array($vcalendar->VEVENT->DESCRIPTION->getParts())) {
                    if (isset ($vcalendar->VEVENT->DESCRIPTION->getParts()[0])) {
                        $bodyParts[] = $vcalendar->VEVENT->DESCRIPTION->getParts()[0];
                    }
                }
            }
        }

        $note->body = implode(PHP_EOL . self::SEPERATOR . PHP_EOL, $bodyParts);

        if ($vcalendar->VEVENT->DTSTART) {
            $note->dateTimeStart = $vcalendar->VEVENT->DTSTART->getDateTime();
        }

        if ($vcalendar->VEVENT->DTEND) {
            $note->dateTimeEnd = $vcalendar->VEVENT->DTEND->getDateTime();
        }
    }

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    function createCalendarObject($calendarId, $objectUri, $calendarData) {
        if ($calendarId != 1) {
            return null;
        }

        $note = new Note;

        try {
            $this->populateNoteFromCalendarData(
                $note,
                $calendarData,
                $objectUri
            );
        } catch (\DomainException $e) {
            error_log($e->getMessage());
            return null;
        }

        $this->persistance->persist($note);
        return '"' . md5($note->id . '|' . $note->body) . '"';
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    function updateCalendarObject($calendarId, $objectUri, $calendarData) {
        if ($calendarId != 1) {
            return null;
        }

        $note = $this->findOneByUri($objectUri);

        try {
            $this->populateNoteFromCalendarData(
                $note,
                $calendarData,
                $objectUri
            );
        } catch (\DomainException $e) {
            error_log($e->getMessage());
            return null;
        }

        $this->persistance->persist($note);
        return '"' . md5($note->id . '|' . $note->body) . '"';
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return void
     */
    function deleteCalendarObject($calendarId, $objectUri) {
        if ($calendarId != 1) {
            return null;
        }

        $note = $this->findOneByUri($objectUri);
        if ($note) {
            $this->persistance->remove($note);
        }
    }

}