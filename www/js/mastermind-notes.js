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

    var notesList = this;

    notesList.noteBlank = {
      body: null
    }
    
    notesList.blah = 'sdfgsdf\ngsdfgsdfg'
    $http.get("api.php")
      .then(function(response) {
          //console.log(response.data)
          notesList.notes = response.data
          /*notesList.notes = [
            {text:'learn 2345AngularJS'},
            {text:'build an srgjioAngularJS app'}];*/
          //$scope.myWelcome = response.data;
      });

    /*var notesList = this;
    notesList.notes = [
      {text:'learn AngularJS'},
      {text:'build an AngularJS app'}];
 */
    notesList.addTodo = function() {
      notesList.notes.push({text: notesList.todoText});
      notesList.todoText = '';
    };
 
    notesList.remaining = function() {
      var count = 0;
      angular.forEach(notesList.todos, function(todo) {
        count += todo.done ? 0 : 1;
      });
      return count;
    };
    
    notesList.create = function () {
      postData($http, 'api.php', notesList.noteBlank, function () {
        notesList.notes.push(notesList.noteBlank)

        notesList.noteBlank = {
          body: null
        }
      })
      /*
      $http.post("api.php", notesList.noteBlank)
        .then(function(response) {
          notesList.notes.push(notesList.noteBlank)
          console.log(response.data)
          //notesList.notes = response.data
          /*notesList.notes = [
            {text:'learn 2345AngularJS'},
            {text:'build an srgjioAngularJS app'}];*---/
          //$scope.myWelcome = response.data;
      });*/
    }
 /*
    notesList.archive = function() {
      var oldTodos = todoList.todos;
      notesList.todos = [];
      angular.forEach(oldTodos, function(todo) {
        if (!todo.done) notesList.todos.push(todo);
      });
    };*/
  });

