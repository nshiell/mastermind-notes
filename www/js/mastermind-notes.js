/* jshint asi: true */

function postData($http, url, data, callback, callbackError) {
  callbackError = (callbackError) ? callbackError : function () {}
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

var user = null
angular.module('notesApp', [])
  .controller('AuthenticationController', function ($scope, $http) {

    this.isAuthenticated = function () {
      return !user
    }

    this.username = null
    this.password = null

    this.login = function () {
      postData($http, '/authentication', {
        'username' : this.username,
        'password' : this.password
        },
        function (response) {
          // @todo make the return a bool
          if (response.data == 'true') {
            user = {}
          }
        }
      )
    }
  })

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

          user = {}

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
      }).catch(function (e) {
        if (e.status == 403) {
          user = null
        }
      })

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
