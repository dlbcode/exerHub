<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ExerHub - Admin: Exercises Editor</title>
  <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
  <script type="text/javascript" src="//code.jquery.com/jquery-3.6.0.min.js"></script>
  <script type="text/javascript" src="//cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="admin.css">
  <?php 
    require_once '../php/db.php'; 
    $exercises = queryExercises();
    $muscles = query('SELECT * FROM muscles');
    function queryExercises() {
        $result = query('SELECT e.name AS exercise_name, e.type AS exercise_type, e.difficulty, m.name AS muscle_name, em.intensity
            FROM exercises e
            JOIN exercise_muscles em ON e.id = em.exercise_id
            JOIN muscles m ON m.id = em.muscle_id');
        $exercises = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $exerciseName = $row['exercise_name'];
            $muscleName = $row['muscle_name'];
            $intensity = $row['intensity'];
            $exerciseType = $row['exercise_type'];
            $exerciseDifficulty = $row['difficulty'];
            
            if (!isset($exercises[$exerciseName])) {
                $exercises[$exerciseName] = array(
                    'muscles' => array(),
                    'type' => $exerciseType,
                    'difficulty' => $exerciseDifficulty
                );
            }
            $exercises[$exerciseName]['muscles'][$muscleName] = $intensity;
        }  
        return $exercises;
    }
  ?>
</head>
<body class="dark">
  <nav>
    <div class="nav-wrapper">
      <span class="brand-logo" style="margin-left: 60px"><a href="index.html"><i class="material-icons">home</i></a><a href="/admin/index.html">/Admin/</a><span class="sub-page-name">Exercises Editor</span></span>
      <a href="index.html" data-target="side-nav" class="show-on-large sidenav-trigger"><i class="material-icons">menu</i></a>
      <ul class="right" id="top-nav"></ul>
    </div>
  </nav>
<ul class="sidenav" id="side-nav"></ul>
<main class="container" style="display: flex; flex-direction: column;">
  <div id="top-form" style="display: flex; align-items: center;">
    <label for="name" id="new-exercise-label" style="margin-right: 10px;">New Exercise: </label>
    <input type="text" id="new-exercise-name" name="new-exercise-name" placeholder="New Exercise Name" style="flex: 1; margin-right: 10px; height: 40px;">
    <select id="new-exercise-type" name="type" style="flex: 0 0 15%; margin-right: 10px; height: 40px;">
        <option value="" disabled selected>Type</option>
        <option value="Push">Push</option>
        <option value="Pull">Pull</option>
        <option value="Legs">Legs</option>
        <option value="Core">Core</option>
    </select>
    <input type="number" id="new-exercise-difficulty" name="difficulty" placeholder="Difficulty" style="flex: 0 0 10%; margin-right: 10px; height: 40px;">
  </div>
  <div style="display: flex; width: 100%;">
    <div class="left-column" style="height: 80vh; width: 50%; box-sizing: border-box; overflow-y: auto;">
      <table id="exercise-table">
        <thead>
          <tr>
            <th>Name</th>
            <th><select title="Filter by Type"><option value="">All Types</option></select></th>
            <th>Difficulty</th>
            <th>Muscles</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($exercises as $exerciseName => $exerciseData): ?>
            <tr>
              <td><?= htmlspecialchars($exerciseName) ?></td>
              <td><?= htmlspecialchars($exerciseData['type']) ?></td>
              <td><?= htmlspecialchars($exerciseData['difficulty']) ?></td>
              <td>
                <?php foreach ($exerciseData['muscles'] as $muscleName => $intensity): ?>
                  <span><?= htmlspecialchars($muscleName) ?></span> (<?= htmlspecialchars($intensity) ?>)<br>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="right-column" style="height: 80vh; width: 50%; box-sizing: border-box; overflow-y: auto;">
      <?php foreach ($muscles as $muscle): ?>
        <div class="slider-container" style="line-height: 1">
          <label for="slider-<?= $muscle['name'] ?>" class="muscle-label"><?= $muscle['name'] ?>: <span id="slider-value-<?= $muscle['name'] ?>"></span></label>
          <input type="range" style="margin: 0 0 0 0" id="slider-<?= $muscle['name'] ?>" name="<?= $muscle['name'] ?>" min="0" max="10" value="<?= isset($muscle['intensity']) ? $muscle['intensity'] : '0' ?>">

        </div>
      <?php endforeach; ?><br>
      <button class="btn waves-effect waves-light" style="height: 40px !important; display: none;" id="update-button">Update Exercise</button>
      <button class="btn waves-effect waves-light" style="height: 40px !important;" id="add-button">Add Exercise</button>
      <button class="btn waves-effect waves-light" style="height: 40px !important; margin-left: 10px; display: none;" id="delete-button">Delete Exercise</button>
    </div>
  </div>
</main>
<script src="../js/nav.js"></script>
<script>
// Helper function to check if at least one muscle intensity is set
function isMuscleIntensitySet() {
  return $('.slider-container input[type="range"]').toArray().some(function(input) {
    return $(input).val() > 0;
  });
}

$(document).ready(function() {
  var exerciseTable = $('#exercise-table').DataTable({
    paging: false,
    searching: false,
    columnDefs: [{ orderable: false, targets: [1] }]
  });

  exerciseTable.column(1).every(function() {
    var column = this;
    var select = $(this.header()).find('select');
    select.on('change', function() {
      column.search($(this).val()).draw();
    });

    var options = column.data().unique().sort().toArray().map(function(d) {
      return '<option value="' + d + '">' + d + '</option>';
    });
    select.append(options);
  });

  $('.dataTables_filter').hide();
  var exerciseData = <?php echo json_encode($exercises); ?>;

  // Handle slider and label updates
  function handleSliderLabelUpdates() {
    $('.slider-container input[type="range"]').each(function() {
      var muscleName = $(this).attr('name');
      var intensity = $(this).val();
      $('#slider-value-' + muscleName).text(intensity);
      $(this).prev('.muscle-label').toggleClass('dot', intensity > 0);
    });
  }

  // Update muscle sliders when exercise is clicked
  $('#exercise-table tbody').on('click', 'tr', function() {
    var $this = $(this);
    $this.siblings().removeClass('selected');
    $this.toggleClass('selected', !$this.hasClass('selected'));
    var showForm = !$this.hasClass('selected');
    $('#top-form, #add-button').toggle(showForm);
    $('#update-button, #delete-button').toggle(!showForm);

    // Reset all sliders and labels to zero
    $('.slider-container input[type="range"]').val(0).prev('.muscle-label').removeClass('dot').find('span').text(0);

    if (!showForm) {
      var exerciseName = $this.find('td:first-child').text();
      var muscles = exerciseData[exerciseName].muscles;
      for (var muscleName in muscles) {
        if (muscles.hasOwnProperty(muscleName)) {
          var intensity = muscles[muscleName];
          $('#slider-' + muscleName).val(intensity);
          $('#slider-value-' + muscleName).text(intensity);
          $('#slider-' + muscleName).prev('.muscle-label').toggleClass('dot', intensity > 0);
        }
      }
    }

    handleSliderLabelUpdates();
  });

  // Add event listener for slider changes
  $('.slider-container input[type="range"]').on('input', handleSliderLabelUpdates);

  function handleAjax(url, type, data, successCallback, errorCallback) {
    $.ajax({
      url: url,
      type: 'POST',
      data: data,
      success: successCallback,
      error: errorCallback
    });
  }

  function updateExerciseMuscles(exerciseName, isUpdate, successCallback) {
    var updates = [];
    var deletions = [];
    var insertions = [];

    $('.slider-container input[type="range"]').each(function() {
      var muscleName = $(this).attr('name');
      var intensity = $(this).val();
      var existingIntensity = exerciseData[exerciseName].muscles[muscleName] || 0;

      if (intensity > 0 && existingIntensity > 0) {
        if (intensity !== existingIntensity) {
          updates.push({
            exercise: exerciseName,
            muscle: muscleName,
            intensity: intensity
          });
        }
      } else if (intensity > 0 && existingIntensity === 0) {
        insertions.push({
          exercise: exerciseName,
          muscle: muscleName,
          intensity: intensity
        });
      } else if (intensity < 1 && existingIntensity > 0) {
        deletions.push({
          exercise: exerciseName,
          muscle: muscleName
        });
      }
    });

    var updatePromises = updates.map(function(update) {
      return new Promise(function(resolve, reject) {
        var query = 'UPDATE exercise_muscles em INNER JOIN muscles m ON em.muscle_id = m.id INNER JOIN exercises e ON em.exercise_id = e.id SET em.intensity = ? WHERE m.name = ? AND e.name = ?';
        var params = [update.intensity, update.muscle, exerciseName];

        handleAjax('../php/db.php', 'POST', {
          query: query,
          params: params
        }, resolve, reject);
      });
    });

    var insertionPromises = insertions.map(function(insertion) {
      return new Promise(function(resolve, reject) {
        var query = 'INSERT INTO exercise_muscles (exercise_id, muscle_id, intensity) SELECT (SELECT id FROM exercises WHERE name = ?), (SELECT id FROM muscles WHERE name = ?), ? FROM dual';
        var params = [insertion.exercise, insertion.muscle, insertion.intensity];

        handleAjax('../php/db.php', 'POST', {
          query: query,
          params: params
        }, resolve, reject);
      });
    });

    var deletionPromises = deletions.map(function(deletion) {
      return new Promise(function(resolve, reject) {
        var query = 'DELETE em FROM exercise_muscles em INNER JOIN exercises e ON e.id = em.exercise_id INNER JOIN muscles m ON m.id = em.muscle_id WHERE e.name = ? AND m.name = ?';
        var params = [deletion.exercise, deletion.muscle];
        handleAjax('../php/db.php', 'POST', {
          query: query,
          params: params
        }, resolve, reject);
      });
    });

    var allPromises = updatePromises.concat(insertionPromises, deletionPromises);
    Promise.all(allPromises)
      .then(function() {
        var query = 'SELECT e.name AS exercise_name, m.name AS muscle_name, em.intensity ' +
          'FROM exercises e ' +
          'JOIN exercise_muscles em ON e.id = em.exercise_id ' +
          'JOIN muscles m ON m.id = em.muscle_id ' +
          'WHERE e.name = ?';
        var params = [exerciseName];
        handleAjax('../php/db.php', 'POST', {
          query: query,
          params: params
        }, function(response) {
          response = JSON.parse(response);

          var updatedMuscles = {};
          response.forEach(function(row) {
            var muscleName = row['muscle_name'];
            var intensity = row['intensity'];
            updatedMuscles[muscleName] = intensity;
          });

          exerciseData[exerciseName].muscles = updatedMuscles;

          var exerciseRow = $('#exercise-table tbody tr.selected');
          var newExerciseData = '';
          for (var muscleName in updatedMuscles) {
            if (updatedMuscles.hasOwnProperty(muscleName)) {
              var intensity = updatedMuscles[muscleName];
              newExerciseData += '<span>' + muscleName + '</span> (' + intensity + ')<br>';
            }
          }
          exerciseRow.find('td:last-child').html(newExerciseData);

          if (typeof successCallback === 'function') {
            successCallback(null, updates);
          }
        }, function(error) {
          console.error(error);
          alert('An error occurred while fetching the updated muscle data.');
        });
      })
      .catch(function(error) {
        console.error(error);
        alert('An error occurred while updating exercise muscles.');
      });
  }

  $('#update-button').click(function() {
    var exerciseName = $('#exercise-table tbody tr.selected td:first-child').text();
    if (!exerciseName || !isMuscleIntensitySet()) {
      alert('Please select an exercise.');
      return;
    }
    updateExerciseMuscles(exerciseName, true, function(response, updates) {
      // Do something with the updates if needed
    });
  });

  $('#delete-button').click(function() {
    var exerciseName = $('#exercise-table tbody tr.selected td:first-child').text();
    if (!exerciseName) {
      alert('Please select an exercise.');
      return;
    }

    if (confirm('Are you sure you want to delete this exercise?')) {
      handleAjax('../php/db.php', 'POST', {
        query: 'DELETE em FROM exercise_muscles em INNER JOIN exercises e ON e.id = em.exercise_id WHERE e.name = ?',
        params: [exerciseName]
      }, function(response) {
        handleAjax('../php/db.php', 'POST', {
          query: 'DELETE FROM exercises WHERE name = ?',
          params: [exerciseName]
        }, function(response) {
          window.location.reload();
        }, function(error) {
          console.error(error);
          alert('An error occurred while deleting the exercise.');
        });
      }, function(error) {
        console.error(error);
        alert('An error occurred while deleting the exercise muscles.');
      });
    }
  });

  $('#add-button').click(function() {
    var exerciseName = $('#new-exercise-name').val();
    var exerciseType = $('#new-exercise-type').val();
    var exerciseDifficulty = $('#new-exercise-difficulty').val();

    if (!exerciseName || !exerciseType || !exerciseDifficulty || !isMuscleIntensitySet()) {
      alert('Please enter an exercise name, type, difficulty, and at least one muscle intensity.');
      return;
    }

    handleAjax('../php/db.php', 'POST', {
      query: 'INSERT INTO exercises (name, type, difficulty) VALUES (?, ?, ?)',
      params: [exerciseName, exerciseType, exerciseDifficulty],
    }, function(response) {
      updateExerciseMuscles(exerciseName, false, function(err, updates) {
        if (!err) {
          window.location.reload();
        }
      });
    }, function(error) {
      console.error(error);
      alert('An error occurred while adding a new exercise.');
    });
  });
});
</script>
</body>
</html>