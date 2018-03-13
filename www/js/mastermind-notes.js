/* jshint asi: true */

function postData($http, url, data, callback) {
  $http({
    method: 'POST',
    url: url,
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    transformRequest: function(obj) {
        var str = []
        for (var p in obj) {
          if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + '=' + encodeURIComponent(obj[p]))
          }
        }
        return str.join("&")
    },
    data: data
  }).then(callback)
}

angular.module('notesApp', [])
  .controller('NotesListController', function ($scope, $http) {

    const titleLength = 20
    var notesList = this;

    notesList.noteBlank = {
      body: null
    }

    $http.get("api.php")
      .then(function(response) {
          notesList.notes = response.data
          notesList.notes.push({
            body: null
          })

          notesList.notes.forEach(function (e) {
            Object.defineProperty(e, 'title', {
              get: function() {
                if (!this.body) {
                  return ''
                }

                if (this.body.length > titleLength) {// &hellip;
                  return this.body.substring(0, titleLength) + '\u2026'
                }

                return this.body
              }
            })
          })
      });

    notesList.show = function (note) {
      $scope.noteActive = note
    }
    
    notesList.remove = function (note) {
      if (confirm('Remove ' + note.title + '?')) {
        $http.delete('/api.php/' + note.id).then(
          function () {
            notesList.notes.forEach(function (item, index, notes) {
              if (note.id == item.id) {
                notes.splice(index, 1)
              }
            })
          }, 
          function (){
            alert('can\'t delete')
          }
        )
      }
    }
    
    notesList.create = function () {
      postData($http, 'api.php', notesList.notes[notesList.notes.length - 1], function () {

        var blank = {
          body: null
        }

        Object.defineProperty(blank, 'title', {
          get: function() {
            if (!this.body) {
              return ''
            }

            if (this.body.length > 10) {// &hellip;
              return this.body.substring(0, 10) + '\u2026'
            }

            return this.body
          }
        })

        notesList.notes.push(blank)
      })
    }
  });
