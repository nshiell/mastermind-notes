<?php require_once (__DIR__ . '/../go.php'); go(!empty ($hasRun)) ?>
<html data-ng-app="notesApp">
  <head>
    <!--<script src="/js/mastermind-notes.js"></script>-->
    <script src="/js/angular/angular.min.js"></script>
    <script src="/js/mastermind-notes.js"></script>
    <link rel="stylesheet" href="/css/page.css" />
  </head>
  <body>
    <div data-ng-controller="NotesListController as notesList">
      <aside>
        <div data-ng-controller="AuthenticationController as auth" data-ng-show="auth.isAuthenticated()">
          <form data-ng-submit="auth.login()">
            <label>
              <span style="display: block">Username:</span>
              <input type="text" data-ng-model="auth.username" />
            </label>

            <label>
              <span style="display: block">Password:</span>
              <input type="password" data-ng-model="auth.password" />
            </label>

            <p>
              <input type="submit" value="Login" />
            </p>
          </form>
        </div>

        <ul>
          <li data-ng-repeat="note in notesList.notes">
            <input type="button" data-ng-click="notesList.remove(note)" value="X" />
            <a href="" data-ng-click="notesList.show(note)">{{note.title}}</a>
          </li>
        </ul>
        <a href="/logout.php">Logout</a>
      </aside>

      <main>
        <form data-ng-submit="notesList.create()">
          <fieldset>
            <input type="submit" value="Save and add Another" />
          </fieldset>
          <!--<textarea data-ng-model="notesList.notes[notesList.notes.length - 1].body"></textarea>-->
          <textarea data-ng-model="notesList.notes[notesList.notes.length - 1].body"></textarea>
          <article class="note-active">{{ noteActive.body }}</article>
        </form>
      </main>
    </div>
  </body>
</html>