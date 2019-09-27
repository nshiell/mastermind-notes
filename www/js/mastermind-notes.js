/* jshint asi: true */
/* jshint multistr: true */

var events = []
var currentId = null

function drawCalendarForToday(eventsData) {
    function dateToTime(date) {
        var hours = date.getHours()
        var mins = date.getMinutes()

        var amPm = 'am'
        if (hours > 12) {
            amPm = 'pm'
            hours-= 12
        }

        var hoursString = (hours < 10) ? '0' + hours : hours
        var minsString = (mins < 10) ? '0' + mins : mins

        var diff = hoursString + ':' + minsString + amPm

        const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds

        var diffDays = Math.round(Math.abs((new Date() - date) / oneDay));

        if (diffDays) {
            diff+= ' (N) dayS ago'
                .replace('N', diffDays)
                .replace('S', (diffDays > 1) ? 's' : '')
        }

        return diff
    }
    var $container = document.querySelector('.calendar')

    function EventCal(dateStart, dateEnd, body, id) {
        this.dateStart = dateStart.getTime()/1000|0
        // @todo not great, think about a better way of doing this!
        this.dateEnd = (dateEnd) ? dateEnd.getTime()/1000|0 : null
        this.body = body
        this.id = id
    }

    eventsData.forEach(function (eventData) {
            if (eventData.dateTimeStart) {
                var dateEnd = null
                if (eventData.dateTimeEnd) {
                    dateEnd = new Date(eventData.dateTimeEnd.date)
                }

                events.push(new EventCal(
                    new Date(eventData.dateTimeStart.date),
                    dateEnd,
                    eventData.body,
                    eventData.id
                ))
            }
      })

    var today = new Date()
    var month = new Month(today)

    function epochToDate(seconds) {
        if (seconds === null) {
            return null
        }

        var date = new Date(0)
        date.setUTCSeconds(seconds)
        return date
    }

    month.drawCalendar($container, 1, events,
        function (eventsForDate) {
            function escapeHtml(unsafe) {
                return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;")
            }

            var $pane = document.querySelector('.pane.note')
            $pane.innerHTML = ''

            if (eventsForDate.length) {
                eventsForDate.forEach(function (note) {
                    $pane.innerHTML+=
                        '<b>' + escapeHtml(note.body) + '</b><br />' +
                        '<input type="button" value="Edit" \
                            onclick="loadEdit(this)" \
                            class="edit edit-id-' + escapeHtml(note.id) + '" />\
                        <input type="button" value="Delete" \
                            onclick="deleteConfirm(this)" \
                            class="delete delete-id-' + escapeHtml(note.id) + '" /><br />' +
                        epochToDate(note.dateStart) + '<br />' +
                        epochToDate(note.dateEnd) + '<hr />'
                })
            }
        }
    )

    var $today = document.querySelector('.today table')
    var eventsFortoday = month.getEventsForDate(today)

    if (eventsFortoday.length) {
        eventsFortoday.forEach(function (event) {
            var s = new Date(event.dateStart * 1000)
            var de = new Date(0); // The 0 there is the key, which sets the date to the epoch
            de.setUTCSeconds(event.dateEnd);
            $today.innerHTML+= '\
                <tr>\
                    <td>BODY</td>\
                    <td>START</td>\
                </tr>'
                .replace('BODY', event.body)
                .replace('START', dateToTime(s))
        })
    } else {
        $today.innerHTML = '<tr><td>None</td><tr>'
    }
}

function saveCurrent() {
    var $form = document.querySelector('form.editor')

    save({
        body          : $form.elements.namedItem('body').value,
        dateTimeStart : $form.elements.namedItem('dateTimeStart').value,
        dateTimeEnd   : $form.elements.namedItem('dateTimeEnd').value
    }, currentId)
}

function executeDelete(id) {
    var xhr = new XMLHttpRequest()
    xhr.open('DELETE', '/api.php/notes/' + id)
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            //var data = JSON.parse(xhr.responseText)
            location.reload()
        } else {
            alert('failled\n' + xhr.responseText)
        }
    };
    xhr.send()
}

function save(data, id) {
    function param(object) {
        var encodedString = '';
        for (var prop in object) {
            if (object.hasOwnProperty(prop)) {
                if (encodedString.length > 0) {
                    encodedString += '&';
                }
                encodedString += encodeURI(prop + '=' + object[prop]);
            }
        }
        return encodedString;
    }

    var xhr = new XMLHttpRequest()
    if (id) {
        xhr.open('POST', '/api.php/notes/' + id)
    } else {
        xhr.open('POST', '/api.php/notes')
    }

    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    xhr.onload = function() {
        if (xhr.status === 200 || xhr.status === 201) {
            //var data = JSON.parse(xhr.responseText)
            location.reload()
        } else {
            alert('failled\n' + xhr.responseText)
        }
    };
    xhr.send(param(data))
}

document.addEventListener("DOMContentLoaded", function () {
    if (document.body.className.indexOf('logged-in') == -1) {
        location.href = '/login.php'
        return
    }

    var xhr = new XMLHttpRequest()
    xhr.open('GET', '/api.php/notes')
    xhr.onload = function () {
        if (xhr.status === 200) {
            drawCalendarForToday(JSON.parse(xhr.responseText))
        } else {
            alert('Request failed.  Returned status of ' + xhr.status)
        }
    }
    xhr.send()
})

function getIdFromClassNames(classNames) {
    var classes = classNames.split(' ')
    for (var i in classes) {
        if (classes[i].substring(0, 8) == 'edit-id-') {
            return classes[i].substring(8)
        }

        if (classes[i].substring(0, 10) == 'delete-id-') {
            return classes[i].substring(10)
        }
    }
}

function deleteConfirm($button) {
    var id = getIdFromClassNames($button.className)
    var event = null
    events.some(function (eventItem) {
        if (eventItem.id == id) {
            event = eventItem
            return true
        }
    })

    if (confirm('Delete "' + event.body.substring(0, 20) + '"?')) {
        executeDelete(event.id)
    }
}

function loadEdit($button) {
    var id = getIdFromClassNames($button.className)
    var event = null
    events.some(function (eventItem) {
        if (eventItem.id == id) {
            event = eventItem
            return true
        }
    })
    const $form = document.querySelector('form.editor')

    $form.elements.namedItem('body').value = event.body

    function formatDateFromEpoch(epoch) {
        function pad(number) {
            return (number < 10) ? '0' + number : number
        }
        var d = new Date(epoch * 1000)
        return d.getFullYear() + '-' +
            pad(d.getMonth() + 1) + '-' +
            pad(d.getDate()) + 'T' +
            pad(d.getHours()) + ':' +
            pad(d.getMinutes()) + ':' +
            pad(d.getSeconds())
    }

    if (event.dateStart) {
        $form.elements.namedItem('dateTimeStart').value = formatDateFromEpoch(event.dateStart)
    } else {
        $form.elements.namedItem('dateTimeStart').value = ''
    }

    if (event.dateEnd) {
        $form.elements.namedItem('dateTimeEnd').value = formatDateFromEpoch(event.dateEnd)
    } else {
        $form.elements.namedItem('dateTimeEnd').value = ''
    }

    currentId = event.id
}