window.onload = function () {
  populateDateList();
};

function populateDateList() {
  const dateList = document.getElementById("date-list");
  let listHTML = "";
  for (let i = 0; i < 14; i++) {
    let date = new Date();
    date.setDate(date.getDate() + i);
    let day = date.toLocaleDateString("da-dk", { weekday: "long" });
    let month = date.toLocaleDateString("da-dk", { month: "long" });
    let dateNumber = date.getDate();
    listHTML += `<li onclick="selectDate('${
      date.toISOString().split("T")[0]
    }')">${day} d. ${dateNumber}. ${month} </li>`;
  }
  dateList.innerHTML = listHTML;
}

function selectDate(date) {
  document.getElementById("selected-date").textContent = new Date(
    date
  ).toDateString();
  document.getElementById("date-list").style.display = "none";
  // Here you can also set the value to a hidden input if you need to submit the date.
}

function toggleDatePicker() {
  const dateList = document.getElementById("date-list");
  if (dateList.style.display === "none" || dateList.style.display === "") {
    dateList.style.display = "block";
  } else {
    dateList.style.display = "none";
  }
}

// Clicking outside the date picker will close it
document.addEventListener("click", function (event) {
  let isClickInsideElement = document
    .querySelector(".custom-date-picker")
    .contains(event.target);
  if (!isClickInsideElement) {
    document.getElementById("date-list").style.display = "none";
  }
});


