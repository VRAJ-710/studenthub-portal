// ============================================================
// Practical 6 - Fetch API, Search, Filter, Sort, Pagination
// ============================================================

// ----- GLOBAL VARIABLES -----
var allData = [];         // stores all fetched records
var filteredData = [];    // stores records after search/filter/sort
var currentSection = "";  // which section is active: "events", "students", "faqs"
var currentPage = 1;      // current page number
var itemsPerPage = 5;     // how many items to show per page


// =========================
// 1. LOAD SECTION (fetch JSON)
// =========================
function loadSection(section) {
  currentSection = section;
  currentPage = 1;

  // show loading, hide error
  document.getElementById("loadingMsg").style.display = "block";
  document.getElementById("errorMsg").style.display = "none";
  document.getElementById("dataContainer").innerHTML = "";
  document.getElementById("paginationContainer").innerHTML = "";

  // clear search box
  document.getElementById("searchBox").value = "";

  // decide which JSON file to fetch
  var url = "../data/" + section + ".json";

  // ----- FETCH API CALL -----
  fetch(url)
    .then(function (response) {
      // check if response is ok
      if (!response.ok) {
        throw new Error("Failed to fetch " + section + " data");
      }
      // parse JSON
      return response.json();
    })
    .then(function (data) {
      // hide loading
      document.getElementById("loadingMsg").style.display = "none";

      // store data globally
      allData = data;
      filteredData = data;

      // set up filter and sort options for this section
      setupFilterOptions(section, data);
      setupSortOptions(section);

      // render the data
      renderData();
    })
    .catch(function (error) {
      // hide loading, show error
      document.getElementById("loadingMsg").style.display = "none";
      document.getElementById("errorMsg").style.display = "block";
      document.getElementById("errorMsg").textContent = "Error: " + error.message;
    });
}


// =========================
// 2. SETUP FILTER OPTIONS
// =========================
function setupFilterOptions(section, data) {
  var dropdown = document.getElementById("filterDropdown");
  dropdown.innerHTML = '<option value="all">All</option>';

  // get unique categories based on section
  var categories = [];

  if (section === "events") {
    // get unique event categories
    for (var i = 0; i < data.length; i++) {
      if (categories.indexOf(data[i].category) === -1) {
        categories.push(data[i].category);
      }
    }
  } else if (section === "students") {
    // get unique departments
    for (var i = 0; i < data.length; i++) {
      if (categories.indexOf(data[i].department) === -1) {
        categories.push(data[i].department);
      }
    }
  } else if (section === "faqs") {
    // get unique FAQ categories
    for (var i = 0; i < data.length; i++) {
      if (categories.indexOf(data[i].category) === -1) {
        categories.push(data[i].category);
      }
    }
  }

  // add each category as an option
  for (var i = 0; i < categories.length; i++) {
    var option = document.createElement("option");
    option.value = categories[i];
    option.textContent = categories[i];
    dropdown.appendChild(option);
  }
}


// =========================
// 3. SETUP SORT OPTIONS
// =========================
function setupSortOptions(section) {
  var dropdown = document.getElementById("sortDropdown");
  dropdown.innerHTML = '<option value="default">Default</option>';

  if (section === "events") {
    dropdown.innerHTML += '<option value="title-asc">Title A-Z</option>';
    dropdown.innerHTML += '<option value="title-desc">Title Z-A</option>';
    dropdown.innerHTML += '<option value="date-asc">Date (Old First)</option>';
    dropdown.innerHTML += '<option value="date-desc">Date (New First)</option>';
  } else if (section === "students") {
    dropdown.innerHTML += '<option value="name-asc">Name A-Z</option>';
    dropdown.innerHTML += '<option value="name-desc">Name Z-A</option>';
    dropdown.innerHTML += '<option value="cgpa-asc">CGPA Low-High</option>';
    dropdown.innerHTML += '<option value="cgpa-desc">CGPA High-Low</option>';
  } else if (section === "faqs") {
    dropdown.innerHTML += '<option value="question-asc">Question A-Z</option>';
    dropdown.innerHTML += '<option value="question-desc">Question Z-A</option>';
  }
}


// =========================
// 4. SEARCH + FILTER + SORT
// =========================
function applySearchAndFilter() {
  var searchText = document.getElementById("searchBox").value.toLowerCase();
  var filterValue = document.getElementById("filterDropdown").value;
  var sortValue = document.getElementById("sortDropdown").value;

  // ----- STEP 1: FILTER using array filter() -----
  filteredData = allData.filter(function (item) {
    if (filterValue === "all") return true;

    if (currentSection === "events") return item.category === filterValue;
    if (currentSection === "students") return item.department === filterValue;
    if (currentSection === "faqs") return item.category === filterValue;
  });

  // ----- STEP 2: SEARCH using array filter() -----
  if (searchText !== "") {
    filteredData = filteredData.filter(function (item) {
      if (currentSection === "events") {
        return item.title.toLowerCase().indexOf(searchText) !== -1 ||
               item.description.toLowerCase().indexOf(searchText) !== -1;
      }
      if (currentSection === "students") {
        return item.name.toLowerCase().indexOf(searchText) !== -1 ||
               item.department.toLowerCase().indexOf(searchText) !== -1;
      }
      if (currentSection === "faqs") {
        return item.question.toLowerCase().indexOf(searchText) !== -1 ||
               item.answer.toLowerCase().indexOf(searchText) !== -1;
      }
    });
  }

  // ----- STEP 3: SORT using array sort() -----
  if (sortValue !== "default") {
    filteredData.sort(function (a, b) {
      if (sortValue === "title-asc") return a.title.localeCompare(b.title);
      if (sortValue === "title-desc") return b.title.localeCompare(a.title);
      if (sortValue === "date-asc") return a.date.localeCompare(b.date);
      if (sortValue === "date-desc") return b.date.localeCompare(a.date);
      if (sortValue === "name-asc") return a.name.localeCompare(b.name);
      if (sortValue === "name-desc") return b.name.localeCompare(a.name);
      if (sortValue === "cgpa-asc") return a.cgpa - b.cgpa;
      if (sortValue === "cgpa-desc") return b.cgpa - a.cgpa;
      if (sortValue === "question-asc") return a.question.localeCompare(b.question);
      if (sortValue === "question-desc") return b.question.localeCompare(a.question);
      return 0;
    });
  }

  // reset to page 1 after search/filter
  currentPage = 1;
  renderData();
}


// =========================
// 5. RENDER DATA (with pagination)
// =========================
function renderData() {
  var container = document.getElementById("dataContainer");
  container.innerHTML = "";

  if (filteredData.length === 0) {
    container.innerHTML = "<p>No records found.</p>";
    document.getElementById("paginationContainer").innerHTML = "";
    return;
  }

  // ----- PAGINATION LOGIC using array slice() -----
  var startIndex = (currentPage - 1) * itemsPerPage;
  var endIndex = startIndex + itemsPerPage;
  var pageData = filteredData.slice(startIndex, endIndex);

  // ----- RENDER ITEMS using array map() and join() -----
  var html = "";

  if (currentSection === "events") {
    html = pageData.map(function (event) {
      return '<div class="card">' +
        '<h3>' + event.title + '</h3>' +
        '<p><strong>Date:</strong> ' + event.date + '</p>' +
        '<p><strong>Category:</strong> ' + event.category + '</p>' +
        '<p>' + event.description + '</p>' +
        '</div>';
    }).join("");
  }

  if (currentSection === "students") {
    html = pageData.map(function (student) {
      return '<div class="card">' +
        '<h3>' + student.name + '</h3>' +
        '<p><strong>Department:</strong> ' + student.department + '</p>' +
        '<p><strong>Semester:</strong> ' + student.semester + '</p>' +
        '<p><strong>CGPA:</strong> ' + student.cgpa + '</p>' +
        '</div>';
    }).join("");
  }

  if (currentSection === "faqs") {
    html = pageData.map(function (faq) {
      return '<div class="card">' +
        '<h3>' + faq.question + '</h3>' +
        '<p><strong>Category:</strong> ' + faq.category + '</p>' +
        '<p>' + faq.answer + '</p>' +
        '</div>';
    }).join("");
  }

  container.innerHTML = html;

  // render pagination buttons
  renderPagination();
}


// =========================
// 6. RENDER PAGINATION BUTTONS
// =========================
function renderPagination() {
  var container = document.getElementById("paginationContainer");
  container.innerHTML = "";

  var totalPages = Math.ceil(filteredData.length / itemsPerPage);

  // show info text
  var info = document.createElement("span");
  info.textContent = "Page " + currentPage + " of " + totalPages +
    " (" + filteredData.length + " records)  ";
  container.appendChild(info);

  // Previous button
  if (currentPage > 1) {
    var prevBtn = document.createElement("button");
    prevBtn.textContent = "Previous";
    prevBtn.onclick = function () {
      currentPage--;
      renderData();
    };
    prevBtn.style.marginRight = "5px";
    container.appendChild(prevBtn);
  }

  // Next button
  if (currentPage < totalPages) {
    var nextBtn = document.createElement("button");
    nextBtn.textContent = "Next";
    nextBtn.onclick = function () {
      currentPage++;
      renderData();
    };
    container.appendChild(nextBtn);
  }
}
