<?php

namespace NShiell\MastermindNotes\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\CalDAV\Backend\AbstractBackend;
use Doctrine\Common\Persistence\ObjectRepository;

/**
 * Mastermind Notes CalDAV backend.
 * To make this class work, you absolutely need to have the PropertyStorage
 * plugin enabled
 */
class MastermindNotes extends AbstractBackend
{
    /** @var ObjectRepository */
    private $noteRepo;

    /** @var string */
    private $username;

    public function __construct(ObjectRepository $noteRepo, string $username)
    {
        $this->noteRepo = $noteRepo;
        $this->username = $username;
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
            $vcalendar = new \Sabre\VObject\Component\VCalendar([
                'VEVENT' => [
                    'UID'     => $note->id,
                    'SUMMARY' => $note->body,
                    'DTSTART' => $note->dateTimeStart,
                    'DTEND'   => $note->dateTimeEnd
                ]
            ]);
            $event = $vcalendar->serialize();
            $events[] = [
                'id'           => $note->id,
                'uri'          => $note->id . '.R713.ics',
                'etag'         => '"' . md5($event) . '"',
                'calendarid'   => 1,
                'size'         => strlen($event),
                'calendardata' => $event
            ];
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

        $uriPars = explode('.', $objectUri);
        $note = $this->noteRepo->findOneBy(['id' => $uriPars[0]]);

        $vcalendar = new \Sabre\VObject\Component\VCalendar([
            'VEVENT' => [
                'UID'     => $note->id,
                'SUMMARY' => $note->body,
                //'DTSTART' => new \DateTime(),
                //'DTEND'   => new \DateTime()
                'DTSTART' => $note->dateTimeStart,
                'DTEND'   => $note->dateTimeEnd
            ]
        ]);
        $event = $vcalendar->serialize();
        return [
            'id'           => $note->id,
            'uri'          => $note->id . '.R713.ics',
            'etag'         => '"' . md5($event) . '"',
            'calendarid'   => 1,
            'size'         => strlen($event),
            'calendardata' => $event
        ];
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
        throw new \Exception(__CLASS__ . '->' . __METHOD__ . 'Not implentted');
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
        throw new \Exception(__CLASS__ . '->' . __METHOD__ . 'Not implentted');
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
        throw new \Exception(__CLASS__ . '->' . __METHOD__ . 'Not implentted');
    }

}