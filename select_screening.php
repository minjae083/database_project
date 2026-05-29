<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
if (!isset($_SESSION['user_id'])) {
    die("로그인이 필요합니다.");
}

require_once 'conn.php';

$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
if ($movie_id <= 0) die("영화 ID 오류");

$sql = "SELECT title, poster_path FROM Movies WHERE movie_id = :movie_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":movie_id", $movie_id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
if (!$row) die("영화 정보가 없습니다.");
$movie_title = $row['TITLE'];
$poster_image = !empty($row['POSTER_PATH']) ? $row['POSTER_PATH'] : 'default.webp';

oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<title><?=htmlspecialchars($movie_title)?> - 상영 시간 & 좌석 선택</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
  body {
    background-color: #d4d4d4;
    color: white;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
  }
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background:rgb(71, 71, 71);
  }
  .logo-img {
    height: 40px;
    vertical-align: middle;
  }
  .search-bar input {
    padding: 8px;
    width: 250px;
    border-radius: 5px;
    border: none;
  }
  .search-bar button {
    padding: 8px 12px;
    margin-left: 10px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
  }
  .auth-buttons {
    position: relative;
    display: flex;
    align-items: center;
  }
  .auth-buttons button {
    margin-left: 10px;
    padding: 8px 12px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    background: #4444aa;
    color: white;
  }
  .username-display {
    margin-right: 15px;
    font-weight: bold;
    font-size: 16px;
  }
  
  .mypage-wrapper {
    position: relative;
    display: inline-block;
  }
  .mypage-popup {
    display: none;
    position: absolute;
    top: 110%;
    right: 0;
    background: #222255;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.6);
    padding: 8px 12px;
    white-space: nowrap;
    z-index: 100;
  }
  .mypage-popup.active {
    display: block;
  }
  .mypage-popup button {
    background: none;
    border: none;
    color: white;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 14px;
    display: block;
    width: 100%;
    text-align: left;
  }
  .mypage-popup button:hover {
    background: #4444aa;
  }
  .nav {
    background-color:rgb(71, 71, 71);
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .nav-logo img {
    height: 40px;
    cursor: pointer;
  }
  .nav-right {
    position: relative;
  }
  .mypage-btn {
    background: none;
    border: none;
    color: white;
    font-size: 16px;
    cursor: pointer;
  }
  .mypage-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 40px;
    background-color: #1c1c4d;
    border: 1px solid #aaa;
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 10;
  }
  .mypage-dropdown a {
    display: block;
    padding: 10px 20px;
    color: white;
    text-decoration: none;
    white-space: nowrap;
  }
  .mypage-dropdown a:hover {
    background-color: #33336d;
  }

  .container {
    display: flex;
    gap: 40px;
    align-items: flex-start;
    padding: 30px;
  }
  .poster {
    width: 300px;
    height: 450px;
    background-image: url('<?=htmlspecialchars($poster_image)?>');
    background-size: cover;
    background-position: center;
    border-radius: 10px;
    box-shadow: 0 0 15px #222;
  }
  .content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .date-time-selection {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }
  label {
    font-size: 18px;
    white-space: nowrap;
  }
  #times {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .time-button {
    background-color: #1c1c50;
    border: none;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  .time-button:hover {
    background-color: #3a3a8f;
  }
  .no-times {
    font-size: 16px;
    color: #aaa;
  }

  #seat-selection {
    margin-top: 10px;
  }
  .seat-map {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .seat-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
  }
  .seat-header .row-label {
    width: 30px;
    margin-right: 8px;
  }
  .seat-header .col-label {
    width: 30px;
    text-align: center;
    margin: 0 4px;
    color: #aaa;
    font-size: 14px;
    user-select: none;
  }
  .empty-space {
    width: 30px;
  }
  .seat-row {
    display: flex;
    align-items: center;
  }
  .seat-row .row-label {
    width: 30px;
    text-align: right;
    margin-right: 8px;
    font-weight: bold;
    font-size: 16px;
    user-select: none;
  }
  label.seat-label {
    margin: 0 4px;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .seat {
    width: 30px;
    height: 30px;
    background-color: white;
    border-radius: 5px;
    cursor: pointer;
    border: none;
    transition: background-color 0.25s;
  }
  .seat.reserved {
    background-color: #555;
    cursor: not-allowed;
    pointer-events: none;
    opacity: 0.5;
  }
  .seat.selected {
    background-color: #4caf50;
  }
  input[type="checkbox"] {
    display: none;
  }
  .complete-btn {
    margin-top: 30px;
    padding: 12px 30px;
    border-radius: 20px;
    background-color: white;
    color: black;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
  }
  .complete-btn:hover {
    background-color: #e0e0e0;
  }
  .seat.disabled {
    background-color: #aaa;
    pointer-events: none;
    opacity: 0.4;
  }
</style>
</head>
<body>

<div class="header">
  <a href="main.php"><img src="images/logo.png" alt="로고" class="logo-img"></a>
  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="영화 제목 입력" />
    <button onclick="goToSearch()">검색</button>
  </div>
  <div class="auth-buttons">
    <?php if ($is_logged_in): ?>
      <span class="username-display"><?= htmlspecialchars($user_name) ?> 님</span>
      <div class="mypage-wrapper">
        <button id="mypageBtn">마이페이지 ▼</button>
        <div class="mypage-popup" id="mypagePopup">
          <button onclick="location.href='mypage.php'">예매 내역</button>
          <button onclick="location.href='my_reviews.php'">리뷰 조회</button>
          <button onclick="location.href='logout.php'">로그아웃</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <div class="poster"></div>

  <div class="content">
    <h1><?=htmlspecialchars($movie_title)?></h1>

    <div class="date-time-selection">
      <label for="date">상영 날짜 선택:</label>
      <div id="calendar"></div>
      <div id="times"><p class="no-times">날짜를 선택하세요.</p></div>
    </div>

    <div id="seat-selection"></div>
  </div>
</div>

<script>
  function goToSearch() {
  const input = document.getElementById('searchInput').value.trim();
  window.location.href = `main.php?search=${encodeURIComponent(input)}`;
  }
  function toggleDropdown() {
    const dropdown = document.getElementById("mypageDropdown");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
  }

  document.addEventListener("click", function(event) {
    const dropdown = document.getElementById("mypageDropdown");
    const button = document.querySelector(".mypage-btn");
    if (!dropdown.contains(event.target) && !button.contains(event.target)) {
      dropdown.style.display = "none";
    }
  });

  const movieId = <?=json_encode($movie_id)?>;
  const timesDiv = document.getElementById('times');
  const seatSelectionDiv = document.getElementById('seat-selection');
  const rows = ['A','B','C','D','E','F','G','H'];
  const seatsPerRow = 14;
  const aislePositions = [4, 12];

  fetch(`fetch_screenings.php?movie_id=${movieId}&date=2000-01-01`)
  .then(res => res.json())
  .then(allData => {
    const uniqueDates = [...new Set(allData.map(item => item.screening_date))];
    flatpickr("#calendar", {
      inline: true,
      dateFormat: "Y-m-d",
      minDate: "today",
      maxDate: new Date().fp_incr(30),
      enable: uniqueDates,
      onChange: function (selectedDates, selectedDateStr) {
        loadScreeningsForDate(selectedDateStr);
      }
    });
  });

  function loadScreeningsForDate(selectedDate) {
    timesDiv.innerHTML = '<p>불러오는 중...</p>';
    seatSelectionDiv.innerHTML = '';

    fetch(`fetch_screenings.php?movie_id=${movieId}&date=${selectedDate}`)
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data) || data.length === 0) {
        timesDiv.innerHTML = '<p class="no-times">해당 날짜에 상영 시간이 없습니다.</p>';
        return;
      }
      timesDiv.innerHTML = '';
      data.forEach(screening => {
        const btn = document.createElement('button');
        btn.className = 'time-button';
        const timeOnly = screening.start_time.split(' ')[1];
        btn.textContent = `${timeOnly} (${screening.screen_name})`;
        btn.onclick = () => {
          loadSeats(screening.screening_id, screening.screen_id);
        };
        timesDiv.appendChild(btn);
      });
    })
    .catch(() => {
      timesDiv.innerHTML = '<p class="no-times">상영 시간 정보를 불러오는 중 오류가 발생했습니다.</p>';
    });
  }

  function loadSeats(screeningId, screenId) {
    seatSelectionDiv.innerHTML = '<p>좌석 정보를 불러오는 중...</p>';

    fetch(`fetch_seats.php?screening_id=${screeningId}&screen_id=${screenId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.seats || data.seats.length === 0) {
        seatSelectionDiv.innerHTML = '<p class="no-times">좌석 정보를 불러올 수 없습니다.</p>';
        return;
      }
      renderSeatMap(screeningId, data.seats);
    })
    .catch(() => {
      seatSelectionDiv.innerHTML = '<p class="no-times">좌석 정보를 불러오는 중 오류가 발생했습니다.</p>';
    });
  }

  function renderSeatMap(screeningId, seats) {
    const seatsByRow = {};
    rows.forEach(r => seatsByRow[r] = []);
    seats.forEach(seat => {
      const row = seat.seat_number.charAt(0);
      const num = parseInt(seat.seat_number.slice(1));
      if (rows.includes(row)) {
        seatsByRow[row][num] = seat;
      }
    });

    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'reserve_seat.php';

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'screening_id';
    hiddenInput.value = screeningId;
    form.appendChild(hiddenInput);

    const seatMapDiv = document.createElement('div');
    seatMapDiv.className = 'seat-map';

    const headerDiv = document.createElement('div');
    headerDiv.className = 'seat-header';
    const rowLabelDiv = document.createElement('div');
    rowLabelDiv.className = 'row-label';
    headerDiv.appendChild(rowLabelDiv);

    for (let i = 1; i <= seatsPerRow; i++) {
      if (aislePositions.includes(i)) {
        const emptySpace = document.createElement('div');
        emptySpace.className = 'empty-space';
        headerDiv.appendChild(emptySpace);
      }
      const colLabel = document.createElement('div');
      colLabel.className = 'col-label';
      colLabel.textContent = i;
      headerDiv.appendChild(colLabel);
    }
    seatMapDiv.appendChild(headerDiv);

    rows.forEach(r => {
      const rowDiv = document.createElement('div');
      rowDiv.className = 'seat-row';
      const rowLabel = document.createElement('div');
      rowLabel.className = 'row-label';
      rowLabel.textContent = r;
      rowDiv.appendChild(rowLabel);

      for (let i = 1; i <= seatsPerRow; i++) {
        if (aislePositions.includes(i)) {
          const emptySpace = document.createElement('div');
          emptySpace.className = 'empty-space';
          rowDiv.appendChild(emptySpace);
        }

        const seat = seatsByRow[r][i];
        if (seat) {
          const label = document.createElement('label');
          label.className = 'seat-label';
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.name = 'seat_ids[]';
          checkbox.value = seat.seat_id;
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'seat';

          if (seat.is_reserved || !seat.is_active) {
            checkbox.disabled = true;
            button.classList.add(seat.is_reserved ? 'reserved' : 'disabled');
          }

          button.addEventListener('click', () => {
            if (checkbox.disabled) return;
            checkbox.checked = !checkbox.checked;
            button.classList.toggle('selected', checkbox.checked);
          });
          checkbox.addEventListener('change', () => {
            button.classList.toggle('selected', checkbox.checked);
          });

          label.appendChild(checkbox);
          label.appendChild(button);
          rowDiv.appendChild(label);
        } else {
          const spacer = document.createElement('div');
          spacer.style.width = '30px';
          spacer.style.height = '30px';
          spacer.style.margin = '0 4px';
          rowDiv.appendChild(spacer);
        }
      }

      seatMapDiv.appendChild(rowDiv);
    });

    form.appendChild(seatMapDiv);

    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'complete-btn';
    submitBtn.textContent = '좌석 선택 완료';
    form.appendChild(submitBtn);

    seatSelectionDiv.innerHTML = '';
    seatSelectionDiv.appendChild(form);
    
  }
  const mypageBtn = document.getElementById('mypageBtn');
    const mypagePopup = document.getElementById('mypagePopup');
    if (mypageBtn) {
      mypageBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        mypagePopup.classList.toggle('active');
      });
      window.addEventListener('click', () => {
        mypagePopup.classList.remove('active');
      });
      mypagePopup.addEventListener('click', e => e.stopPropagation());
    }
</script>

</body>
</html>
