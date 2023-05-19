<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>BWE - Workout Details</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <link rel="stylesheet" href="style.css">
  <?php require_once 'php/db.php'; ?>
</head>
<body class="dark">
<nav>
  <div class="nav-wrapper">
    <span class="brand-logo" style="margin-left: 60px"><a href="index.html">BWE/</a><a href="workouts.php">Workouts/</a><span class="sub-page-name">Workout</span></span>
    <a href="index.html" data-target="side-nav" class="show-on-large sidenav-trigger"><i class="material-icons">menu</i></a>
    <ul class="right" id="top-nav"></ul>
  </div>
</nav>
<ul class="sidenav" id="side-nav"></ul>
<main class="container">
  <div class="row">
    <div class="col s12">
      <div class="col s8">
        <?php
          session_start();
          $userId = $_SESSION['user_id'];
          $workoutId = $_GET['workout_id'];
          $workout_name = $_GET['workout_name'];
          echo "<h5>$workout_name</h5>";
          $workoutItems = fetchWorkoutItems($workoutId);
          displayWorkoutDetails($workoutItems);

          function fetchWorkoutItems($workoutId) {
            global $conn;
            $query = "SELECT ws.type, ws.exercise_id, ws.seconds, ws.sets, e.name as exercise_name FROM workout_sequences ws LEFT JOIN exercises e ON ws.exercise_id = e.id WHERE ws.workout_id = $workoutId";
            $result = query($query);
            $items = array();
            while ($row = mysqli_fetch_assoc($result)) {
              $items[] = $row;
            }
            return $items;
          }

          function displayWorkoutDetails($items) {
            if (empty($items)) {
              echo "<p>No workout found.</p>";
            } else {
              foreach ($items as $item) {
                $exerciseName = $item['exercise_name'] ?: '';
                echo "<h6>{$item['type']} {$exerciseName} {$item['seconds']} {$item['sets']}</h6>";
                // Display other workout details here
              }
            }
          }
        ?>

        <button id="startButton" class="btn">Start</button>
        <div id="timerModal" class="modal">
          <div class="modal-content">
            <h4>Workout Timer</h4>
            <ul id="workoutList"></ul>
          </div>
          <div class="modal-footer">
            <a href="#!" id="pauseButton" class="waves-effect waves-green btn-flat">
              <i class="material-icons" id="pauseIcon">pause</i>
              <i class="material-icons" id="playIcon" style="display: none;">play_arrow</i>
            </a>
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<script>
  var workoutItems = <?php echo json_encode($workoutItems); ?>;
  document.addEventListener('DOMContentLoaded', function() {
    var startButton = document.getElementById('startButton');
    var pauseButton = document.getElementById('pauseButton');
    var pauseIcon = document.getElementById('pauseIcon');
    var playIcon = document.getElementById('playIcon');
    var timerModal = document.getElementById('timerModal');
    var workoutList = document.getElementById('workoutList');
    var currentIndex = 0; // Track the current item index
    var remainingSeconds = 0; // Track the remaining seconds for the current item
    var interval; // Variable to store the interval ID
    var isPaused = false; // Flag to track the paused state

    startButton.addEventListener('click', startWorkout);
    pauseButton.addEventListener('click', togglePause);

    // Listen for modal-close event to reset the currentIndex
    timerModal.addEventListener('modal-close', function() {
      currentIndex = 0;
    });

    function startWorkout() {
      // Clear the existing workout list
      workoutList.innerHTML = '';

      // Populate the workout list
      workoutItems.forEach(function(item) {
        var listItem = createListItem(item);
        workoutList.appendChild(listItem);

        // Duplicate the list item based on the number of sets
        for (var i = 1; i < item.sets; i++) {
          workoutList.appendChild(listItem.cloneNode(true));
        }
      });

      // Start the countdown for the first item
      countdown(workoutItems[currentIndex]);

      // Initialize the modal
      var modalInstance = M.Modal.init(timerModal);
      modalInstance.open();
    }

    function createListItem(item) {
      var li = document.createElement('li');
      var text = document.createTextNode(item.type + ' ' + (item.exercise_name || '') + ' ' + item.seconds + ' seconds');
      li.appendChild(text);
      return li;
    }

    function togglePause() {
      if (isPaused) {
        resumeCountdown(); // If paused, resume the countdown
      } else {
        pauseCountdown(); // If not paused, pause the countdown
      }
      isPaused = !isPaused; // Toggle the paused state
      togglePlayPauseIcons(); // Toggle the display of pause/play icons
    }

    function pauseCountdown() {
      clearInterval(interval); // Clear the interval to pause the countdown
      remainingSeconds = getRemainingSeconds(); // Store the remaining seconds
    }

    function resumeCountdown() {
      countdown(workoutItems[currentIndex], remainingSeconds); // Resume the countdown with remaining seconds
    }

    function togglePlayPauseIcons() {
      if (isPaused) {
        pauseIcon.style.display = 'none'; // Hide pause icon
        playIcon.style.display = 'inline'; // Show play icon
      } else {
        pauseIcon.style.display = 'inline'; // Show pause icon
        playIcon.style.display = 'none'; // Hide play icon
      }
    }

    function countdown(item, seconds) {
      seconds = seconds || item.seconds; // Use the remaining seconds if provided
      var element = workoutList.children[currentIndex];

      interval = setInterval(function() {
        if (item.type === 'Rest') {
          element.textContent = item.type + ' ' + seconds + ' seconds'; // Update the countdown display
        } else {
          element.textContent = item.type + ' ' + (item.exercise_name || '') + ' ' + seconds + ' seconds'; // Update the countdown display
        }

        if (seconds <= 0) {
          clearInterval(interval); // Stop the countdown when it reaches 0

          currentIndex++; // Move to the next item

          // Check if there are more items in the list
          if (currentIndex < workoutItems.length) {
            // Start the countdown for the next item
            countdown(workoutItems[currentIndex]);
          } else {
            // All items have been counted down
            // You can add your desired action here
          }
        }

        seconds--;
      }, 1000);
    }

    function getRemainingSeconds() {
      var element = workoutList.children[currentIndex];
      var timeParts = element.textContent.split(' ');
      return parseInt(timeParts[timeParts.length - 2]); // Extract the remaining seconds from the display text
    }
  });
</script>
</body>
</html>
