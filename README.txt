GRAMMAR VAULT - HOW TO RUN (Windows)
====================================

Grammar Vault needs PHP so it can save games and store student results.
Do steps 1-4 once. After that, you only need step 5 each time.


1. INSTALL PHP (one time)
-------------------------
EASY WAY: double-click install_php.bat. It downloads PHP, installs it to
C:\php, turns on the database driver and adds PHP to your PATH. Then
skip to step 3 (open a NEW terminal afterwards). Manual steps:

 a. Download PHP from  https://windows.php.net/download
    Choose the latest 8.x, "x64", "Non Thread Safe" ZIP.
 b. Unzip it to  C:\php
 c. Add PHP to your PATH:
      Start menu -> search "Environment Variables" -> Edit "Path"
      -> New -> type  C:\php  -> OK
 d. If PHP later complains about a missing DLL, install the free
    "Microsoft Visual C++ Redistributable (x64)" from Microsoft.
 e. Open a NEW terminal (Command Prompt or PowerShell) and type:
      php -v
    You should see a version number.


2. TURN ON THE DATABASE DRIVER (one time)
-----------------------------------------
 a. In a terminal, type:   php --ini
    Look at "Loaded Configuration File".
      - If it says (none): copy  C:\php\php.ini-development  to
        C:\php\php.ini  and run  php --ini  again.
 b. Open php.ini in Notepad. Remove the ";" at the start of these
    lines (add the lines if they are missing):

      extension_dir = "ext"
      extension=pdo_sqlite
      extension=sqlite3

 c. Save the file.
 d. Check it worked:   php -m
    The list should include  pdo_sqlite  and  sqlite3.


3. CHECK THE GAME FOLDER
------------------------
Keep ALL the files together in one folder (for example
Desktop\Grammar Vault). It should contain:

  index2.html          character_customizer.html
  character.js   levels.js    questions.js    glossary.js
  speech.js      voice_input.js   bgm.js      stats_report.js
  tutorial.js    results.js
  student.html   student_api.php            (student log in / register)
  student_progress.html                     (each student's own "My Progress" page)
  save_api.php   results_api.php  router.php    gv_db.php
  gv_stats.php   teacher_report.php         (dashboard numbers, Word/print reports)
  teacher.php    teacher_gate.php    set_teacher_password.php
  start_shared_server.bat
  install_php.bat    start_game.bat    start_teacher.bat
  bgm\  (optional folder for music files)


4. TEACHER ACCOUNTS AND TEACHER NAMES (one time per teacher)
------------------------------------------------------------
Every teacher has their OWN account. A teacher only ever sees the students who
typed their name - other teachers' students are completely separate.

Creating an account:
 - Start the game (step 5), click the "Teacher" button on the main menu, then
   "Create a teacher account". Choose the name your students will see (for
   example "Ms. Rivera"), a username and a password (10+ characters).
 - Anyone who can open the teacher page can create an account, but every
   account only ever sees its own students.
 - Or create accounts for teachers yourself: open a terminal in the game
   folder (or double-click set_teacher_password.bat) and choose option 1.

Teacher name: the name you choose when you create the account (for example
"Ms. Rivera") is what students type when they REGISTER (see step 4b). Results are
saved under that teacher. Capitals, spaces, dots and titles are ignored, so
"rivera", "Ms Rivera" and "MS. RIVERA" all find "Ms. Rivera". Two teachers
cannot have the same name - if you share a last name, add something, for
example "Ms. Rivera (Room 5)". A student who types an unknown name can't start
(unless the teacher already added that student - see 4b).

To list teachers and their names, or reset a password, run
php set_teacher_password.php  (option 3 lists, option 2 resets).
Teachers can change their own password on the dashboard ("Account").

UPGRADING from the single teacher password: nothing to do. Your old password
becomes the account  teacher  (same password), it is called "teacher" until you change it (dashboard > Account > Name students type), and all
results recorded so far belong to it. Other teachers then create their own
accounts. (If you have no teacher password yet, the first account anyone creates
takes over any old results.)


4b. STUDENT ACCOUNTS (new)
--------------------------
Every student has their own account. Students log in with their Student ID
(format 12-3456-789, e.g. the school ID) OR a username.

 REGISTERING - needs the Student ID AND the full name:
   Menu -> "Student Log in" -> Register. The student types their Student ID,
   full name, grade, and their teacher's name (a username is optional).
   The Student ID is checked for the format 12-3456-789 and can only be used once.

 TEACHER ADDS STUDENTS (Teacher page -> Roster):
   - Add one student (Student ID + full name + grade), or paste a whole class,
     one student per line:   12-3456-789, Juan Dela Cruz, 3
   - A student added by the teacher just registers once with their Student ID,
     full name exactly as the teacher typed it, and a password of their own - they
     do not need to type the teacher's name. Until then the Roster shows "Waiting to register".
   - REMOVE a student on the Roster page (asks for confirmation). A student who has
     registered KEEPS their account, password and all results; they just leave your
     class and you no longer see them. If another teacher then adds the same Student
     ID, the student joins that class and all earlier results move with them.
     (A student who never registered has nothing to keep and is simply deleted.)
   - Teachers can only add, see and remove THEIR OWN students.

 PASSWORDS: registering needs the Student ID, the full name AND a password (at least 4
 letters or numbers, typed twice; it cannot be the Student ID). Logging in needs the
 Student ID or username AND the password. Passwords are stored scrambled (hashed) - nobody,
 including teachers, can read them. Results and the "My Progress" page only work for a
 logged-in student, so nobody can see or add results for another student by knowing the ID.
   - Forgot the password? Teacher page -> Roster -> "Reset password" next to the student,
     type a new one and tell the student. (The old password stops working immediately.)
   - Accounts made before passwords existed show "Needs a password" on the Roster. The
     student just tries to log in; the page asks for their full name and a new password.
   - After 8 wrong passwords for one account, it is locked for 10 minutes.
 "Not you? Log out" on the menu switches students on a shared computer. A student
 without a class can still log in and play; their results wait for the next teacher.

 MY PROGRESS (for students): after logging in, the menu shows a green "My Progress"
 button (also on the student log in page). The student sees only THEIR OWN data:
 a next goal, accuracy, stars and levels finished, each level's stars, skills with
 friendly labels and a practice tip, the last days played, 12 badges (First Steps,
 Century Club, Triple Star, Vault Champion, Speedy Reader...) and a short list of
 questions to try again. Teachers see the same numbers in more detail on the dashboard.

 TIMER: every question has 30 seconds (3 questions per character = 1:30 per
 round). When time runs out the answer counts as wrong, the correct answer is
 shown, and it is recorded as "Time ran out" for the teacher. The timer stops
 while the game is paused, the tutorial is open, or a word-meaning pop-up is open.
 (Change Q_TIME in index2.html to use a different limit.)

 TEACHER DASHBOARD (teacher.php):
   Overview  - students registered / active this week, class accuracy, average
               time per answer, timed-out questions, who needs attention (and why),
               progress per level, activity by week, skills, grades.
   Students  - every student's levels and stars, accuracy and trend (last 20
               answers vs the 20 before), average time, timed-out questions,
               skills, last played and status (On track / Needs help / Inactive /
               Not started / Not registered). Filter and sort; click a name for details.
   Roster    - add / remove students (above).
   Reports   - PRINTABLE REPORTS: class report, one report per student, or a
               pack with every student. Choose "Download Word (.docx)" or
               "Print / Save as PDF" (in the print window pick "Save as PDF").
               CSV for Excel is also available.
   Times are shown in Philippine time (change GV_TZ in gv_stats.php).

WEB HOSTING WITHOUT A TERMINAL (for example InfinityFree)
- Choose PHP 8.x in the host's control panel. Upload ALL the files to htdocs.
- Open  https://your-site/gv_check.php  - it lists exactly what is missing. Delete it afterwards.
- Log in with USERNAME  teacher  and your old teacher password (an upgraded old password becomes that account).
- Anyone who opens teacher.php can create a teacher account (see step 4).


4c. TEACHER "I FORGOT MY PASSWORD" (new)
   - Teachers now give an email address when they create their account.
     Teachers who already had an account: log in, open Account, and save a
     "Recovery email" (a red reminder shows until you do).
   - On the teacher login page, click "I forgot my password", then type the
     Username and the Email used at registration.
   - If BOTH match, a new random password is emailed to that address. If either
     is wrong, no email is sent. The page shows the same message either way,
     so nobody can find out which usernames or emails exist.
   - The teacher logs in with the emailed password and is reminded to change it
     in Account. Limits: 3 requests per username and 10 per computer per hour.
   - The email is sent with the settings in mail_config.php:
       * On InfinityFree: set method to 'mail' and from_email to an address on
         your own domain (some free hosts block mail(); if no email arrives,
         use SMTP instead).
       * On a school PC, with Gmail: method 'smtp', host 'smtp.gmail.com',
         port 587, secure 'tls', username = your Gmail address, password = a
         Gmail "App Password" (Google Account > Security > 2-Step
         Verification > App passwords), from_email = the same Gmail address.
     Until mail_config.php is filled in, the forgot-password page tells the
     teacher that email is not set up. Keep mail_config.php private: do not
     share or upload it publicly once it has a password in it.

4d. AUTOCOMPLETE WHEN ADDING STUDENTS (new)
   - Teacher > Roster has a "School list for suggestions" box. Paste lines like
       12-3456-789, Juan Dela Cruz, 3
     (Student ID, full name, optional grade). Names and IDs only; it does not
     add anyone to a class. All teachers share this list.
   - In "Add a student", typing a Student ID (or a name) shows matches. A
     complete ID fills in the name and grade; a name fills in the ID.
   - Students already in a class are left out of the suggestions.

4f. DASHBOARD LEGEND AND PAGING (new)
   - Overview > "Needs attention" shows 10 students per page, with Previous / Next
     and page numbers.
   - New "Legend" tab (and a quick legend under Needs attention) explains the
     colours, student statuses, trend arrows, level boxes, time limits and terms.

5. START THE GAME (every time)
------------------------------
Open a terminal INSIDE the game folder and run:

  php -S localhost:8000 router.php

Leave that window open while people play.

  Game:          http://localhost:8000/index2.html
  Teacher page:  http://localhost:8000/teacher.php  (log in, or create an account)

Do NOT double-click index2.html. Saving and results only work
through the PHP server above.

Tip: in File Explorer, click the address bar of the game folder, type
"cmd", and press Enter. That opens a terminal in the right place.


SHARE THE GAME WITH OTHER DEVICES (no PHP needed for players)
-------------------------------------------------------------
Only the computer that HOSTS the game needs PHP (steps 1-4 above).
On that computer, double-click:

  start_shared_server.bat

It starts the server so other devices on the same Wi-Fi / network can
play, and shows an address like  http://192.168.1.25:8000/index2.html
(also copied to your clipboard). Players just type that address into
their browser - they do not need PHP, or even the game files.
It also opens the teacher dashboard on the host computer.

 - Send players the ADDRESS, not the .html file. A copy of the .html
   on another computer cannot save progress or send results; it only
   works when opened from the host's address.
 - Keep the "Grammar Vault SHARED Server" window open while people play.
 - Windows may ask to allow PHP on the network the first time: click
   Allow and tick Private networks (or run the .bat as administrator once).
 - Every player's results go into the teacher database on the host
   computer (data\grammar_vault.sqlite). Open the teacher dashboard with
   the Teacher button on the main menu, or http://localhost:8000/teacher.php
   on the host computer.
 - The microphone (voice answers) only works on the host computer itself.

6. MAKE SURE IT WORKS
---------------------
 1. Open the game, click "Student Log in" -> Register (Student ID like 12-3456-789,
    full name, password, grade, teacher's name), then Play and answer a question. Watch the
    30-second timer.
 2. Press P to pause, then save to a slot. You should see
    "Game saved to Slot 1".
 3. Open the teacher page and log in as the teacher whose name you typed. The
    student should appear on the Students tab (and NOT for other teachers).
      - If it says "No results yet", wait a few seconds and refresh.
        The game sends results in small batches.


7. STOP THE GAME
----------------
Click the terminal window and press Ctrl+C.


IF SOMETHING GOES WRONG
-----------------------
 "could not find driver"
     Repeat step 2, then stop and restart the server.

 "php is not recognized"
     PATH is not set. Close and reopen the terminal, or redo step 1c.

 "Failed to listen ... address already in use"
     Change 8000 to 8001 in the command AND in the web addresses.

 Microphone / voice answers do not work
     Open the game with  http://localhost:8000/...  (not a file path).
     Voice input only works in Chrome, Edge, or Safari.

 Forgot a teacher password
     Run  php set_teacher_password.php  and choose option 2 (reset password).

 "We couldn't find that teacher"
     Check the spelling against the name shown at the top of the teacher's dashboard,
     or ask the teacher to add you on the Roster page first.

 "Couldn't reach the save server"
     The server window is closed, or the game was opened as a file.
     Start the server (step 5) and use the localhost address.


WHERE THINGS ARE STORED
-----------------------
 Student results:  data\grammar_vault.sqlite
                   Back up this file to keep the results. It contains
                   student names, so keep it private. Always start the
                   game with router.php (step 5), which blocks that
                   folder from being downloaded.

 Save slots:       Kept for the current browser session only. They are
                   cleared when the browser is fully closed.

 Teacher accounts: in the same database (data\grammar_vault.sqlite), with
                   scrambled password hashes - never the passwords themselves.
                   teacher_config.php only holds the old single teacher
                   password hash, if you upgraded (it can be deleted afterwards).


OPTIONAL: MUSIC
---------------
Put MP3/OGG files in a folder named "bgm" next to index2.html,
named menu.mp3, gameplay.mp3, and win.mp3. Without them the game plays
its built-in chiptune music.


GITHUB COPY - FIRST-TIME SETUP
- This copy has no comments and no private data. Two settings files are shipped as examples:
    mail_config.example.php   -> copy to mail_config.php and fill in your email settings
                                 (needed only for the teacher "I forgot my password" email)
    teacher_config.example.php -> optional; only used to migrate an old single teacher password.
                                 Normally leave it out: teachers create their own accounts.
- mail_config.php, teacher_config.php and the data/ database are listed in .gitignore so they are
  never uploaded. Never commit real passwords, hashes or the data/ folder.
