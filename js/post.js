// Global variables
let currentQuestionIndex = 0;
let revealedQuestionCount = 0;

// Navigation Functions
function updateNavButtons() {
  document.getElementById("prevBtn").disabled = currentQuestionIndex === 0;
  document.getElementById("nextBtn").disabled =
    currentQuestionIndex === questions.length - 1;
}

function updatePaginationDots() {
  var dotsContainer = document.querySelector(".pagination-dots");
  dotsContainer.innerHTML = "";
  for (var i = 0; i < questions.length; i++) {
    var dot = document.createElement("span");
    dot.className = "dot" + (i === currentQuestionIndex ? " active" : "");
    dotsContainer.appendChild(dot);
  }
}

// Question Display Functions
function revealExplanation() {
  const explanationContent = document.getElementById("explanation-content");
  const explanationButton = document.getElementById("explanation-button");

  if (explanationContent.style.display === "none") {
    explanationContent.style.display = "block";
    explanationButton.textContent = "Hide Explanation";
  } else {
    explanationContent.style.display = "none";
    explanationButton.textContent = "Explanation";
  }
}

function revealAnswer() {
  // Check if user is logged in or has free reveals left
  if (isLoggedIn || revealedQuestionCount < guestRevealData.freeRevealCount) {
    var rightContainer = document.querySelector(".right-container");
    rightContainer.classList.add("flip");

    revealedQuestionCount++;

    // Store in sessionStorage instead of localStorage
    sessionStorage.setItem(
      `revealedQuestions_${postID}`,
      revealedQuestionCount
    );
  } else {
    // Show modal for ad watch or login
    showAnswerUnlockModal();
  }
}

function showAnswerUnlockModal() {
  // Create a modal dynamically
  const modal = document.createElement("div");
  modal.className = "answer-unlock-modal";
  modal.innerHTML = `
      <div class="modal-content">
          <h2>Unlock More Answers</h2>
          <p>You've reached the limit of free answer reveals.</p>
          <div class="unlock-options">
              <button id="watch-ad-btn">Watch Ad to Unlock</button>
              <button id="login-btn">Login to Unlock All</button>
          </div>
          <button id="close-unlock-modal">Close</button>
      </div>
  `;

  document.body.appendChild(modal);

  // Add event listeners
  document
    .getElementById("watch-ad-btn")
    .addEventListener("click", handleAdWatch);
  document
    .getElementById("login-btn")
    .addEventListener("click", redirectToLogin);
  document
    .getElementById("close-unlock-modal")
    .addEventListener("click", closeUnlockModal);
}

function redirectToLogin() {
  window.location.href = "login.php";
}

function closeUnlockModal() {
  const modal = document.querySelector(".answer-unlock-modal");
  if (modal) {
    modal.remove();
  }
}

function handleAdWatch() {
  // Placeholder for ad watching logic
  // In a real implementation, you'd integrate with an ad network
  alert(
    "Ad watching feature coming soon. This would typically involve showing an ad."
  );

  // After ad is watched, increment reveal count
  revealedQuestionCount++;
  localStorage.setItem(`revealedQuestions_${postID}`, revealedQuestionCount);
  closeUnlockModal();
  revealAnswer();
}

function hideAnswer(event) {
  event.stopPropagation();
  var rightContainer = document.querySelector(".right-container");
  rightContainer.classList.remove("flip");
}

// Navigation Functions
function prevItem() {
  if (currentQuestionIndex > 0) {
    animateTransition("right", () => {
      currentQuestionIndex--;
      updateQuestion();
    });
  }
}

function nextItem() {
  if (currentQuestionIndex < questions.length - 1) {
    animateTransition("left", () => {
      currentQuestionIndex++;
      updateQuestion();
    });
  }
}

function animateTransition(direction, callback) {
  var leftContainer = document.querySelector(".left-container");
  var rightContainer = document.querySelector(".right-container");

  leftContainer.classList.remove("slide-left", "slide-right");
  rightContainer.classList.remove("slide-left", "slide-right", "flip");

  void leftContainer.offsetWidth;
  void rightContainer.offsetWidth;

  if (direction === "left") {
    leftContainer.classList.add("slide-left");
    rightContainer.classList.add("slide-left");
  } else {
    leftContainer.classList.add("slide-right");
    rightContainer.classList.add("slide-right");
  }

  setTimeout(() => {
    callback();
    leftContainer.classList.remove("slide-left", "slide-right");
    rightContainer.classList.remove("slide-left", "slide-right");
  }, 500);
}

function updateQuestion() {
  var question = document.querySelector(".question");
  var answer = document.querySelector(".answer");
  var rightContainer = document.querySelector(".right-container");

  // Clear existing content
  question.innerHTML = "";

  // Add question image if it exists
  if (questions[currentQuestionIndex]["QuestionImageURL"]) {
    const imageContainer = document.createElement("div");
    imageContainer.className = "question-image-container";
    imageContainer.innerHTML = `<img src="${questions[currentQuestionIndex]["QuestionImageURL"]}" alt="Question Image" class="question-image">`;
    question.appendChild(imageContainer);
  }

  // Add question content
  const contentContainer = document.createElement("div");
  contentContainer.className = "question-content-container";
  contentContainer.textContent =
    questions[currentQuestionIndex]["QuestionContent"];
  question.appendChild(contentContainer);

  // Update answer content
  answer.innerHTML = `
        <p>${questions[currentQuestionIndex]["AnswerContent"]}</p>
        ${
          questions[currentQuestionIndex]["AnswerImageURL"]
            ? `<img src="${questions[currentQuestionIndex]["AnswerImageURL"]}" alt="Answer Image" class="answer-image">`
            : ""
        }
        <button id="explanation-button" onclick="revealExplanation()">Explanation</button>
        <div id="explanation-content" style="display: none;">
            <p>${questions[currentQuestionIndex]["Explanation"]}</p>
        </div>
        <button id="hide-button" onclick="hideAnswer(event)">Hide Answer</button>
    `;

  rightContainer.classList.remove("flip");

  updateNavButtons();
  updatePaginationDots();
}

// Rating System Functions
function updateUserStars(userRating) {
  document.querySelectorAll(".star").forEach((star, index) => {
    if (index < userRating) {
      star.classList.add("active");
    } else {
      star.classList.remove("active");
    }
  });
}

function updateAverageRating(avgRating, count) {
  document.getElementById("avg-rating").textContent = avgRating.toFixed(1);
  document.querySelector(".user-count").textContent = `(${count})`;
}

function fetchRatings() {
  fetch("get_ratings.php?postID=" + postID)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayRatings(data.ratings);
      } else {
        alert(data.message || "An error occurred while fetching ratings.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while fetching ratings.");
    });
}

function displayRatings(ratings) {
  const ratingsList = document.getElementById("ratingsList");
  ratingsList.innerHTML = "";

  ratings.forEach((rating) => {
    const ratingDiv = document.createElement("div");
    ratingDiv.className = "rating-item";

    ratingDiv.innerHTML = `
          <div class="rating-user">
              <img src="${rating.profileImage}" alt="Profile" class="rating-profile-img">
              <span class="rating-name">${rating.fullName}</span>
          </div>
          <div class="rating-score">
              <span class="rating-number">${rating.score}</span>
              <span class="rating-star">★</span>
          </div>
      `;

    ratingsList.appendChild(ratingDiv);
  });
}

// Comment System Functions
function handleCommentSubmit(event) {
  event.preventDefault();
  var commentContent = document.getElementById("comment-input").value;
  if (commentContent.trim() === "") return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "submit_comment.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onload = function () {
    if (this.status === 200) {
      var response = JSON.parse(this.responseText);
      if (response.success) {
        addNewComment(response);
        document.getElementById("comment-input").value = "";
      }
    }
  };
  xhr.send(
    "postID=" +
      encodeURIComponent(postID) +
      "&content=" +
      encodeURIComponent(commentContent)
  );
}

function addNewComment(response) {
  var commentsContainer = document.getElementById("comments-container");
  var newComment = document.createElement("div");
  newComment.className = "comment";
  newComment.innerHTML = `
        <img class="comment-profile-image" src="${response.profileImage}" alt="profile">
        <div class="comment-info">
            <div class="commenter-name">${response.username}</div>
            <div class="comment-text">
                <p style="margin-left: 20px;">${response.content}</p>
            </div>
        </div>
    `;
  commentsContainer.insertBefore(newComment, commentsContainer.firstChild);
}

function initializeReportSystem() {
  const reportButton = document.getElementById("report-button");
  const reportModal = document.getElementById("reportModal");
  const closeReport = document.querySelector(".close-report");
  const submitReport = document.getElementById("submit-report");

  if (reportButton) {
    reportButton.addEventListener("click", () => {
      reportModal.style.display = "block";
    });
  }

  if (closeReport) {
    closeReport.addEventListener("click", () => {
      reportModal.style.display = "none";
      clearReportForm();
    });
  }

  if (submitReport) {
    submitReport.addEventListener("click", submitReportHandler);
  }

  // Close modal when clicking outside
  window.addEventListener("click", (event) => {
    if (event.target === reportModal) {
      reportModal.style.display = "none";
      clearReportForm();
    }
  });
}

function clearReportForm() {
  const reasonInput = document.getElementById("report-reason");
  const categoryInput = document.getElementById("report-category");
  const errorElement = document.getElementById("report-error");

  if (reasonInput) reasonInput.value = "";
  if (categoryInput) categoryInput.value = "";

  if (errorElement) {
    errorElement.textContent = "";
    errorElement.style.display = "none";
  }
}

function showReportError(message) {
  let errorElement = document.getElementById("report-error");
  if (!errorElement) {
    errorElement = document.createElement("div");
    errorElement.id = "report-error";
    errorElement.className = "error-message";
    document
      .getElementById("report-form")
      .insertBefore(errorElement, document.getElementById("submit-report"));
  }
  errorElement.textContent = message;
  errorElement.style.display = "block";
}

function submitReportHandler() {
  const reasonInput = document.getElementById("report-reason");
  const categoryInput = document.getElementById("report-category");

  const reason = reasonInput.value.trim();
  const category = categoryInput.value;

  // Client-side validation
  if (!category) {
    showReportError("Please select a report category.");
    categoryInput.focus();
    return;
  }

  if (reason.length < 10 || reason.length > 500) {
    showReportError("Reason must be between 10 and 500 characters.");
    reasonInput.focus();
    return;
  }

  const formData = new FormData();
  formData.append("postID", postID); // Ensure postID is defined globally or passed correctly
  formData.append("reason", reason);
  formData.append("category", category);

  // Disable submit button to prevent multiple submissions
  const submitButton = document.getElementById("submit-report");
  submitButton.disabled = true;
  submitButton.classList.add("loading");

  fetch("submit_report.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        alert("Report submitted successfully.");
        reportModal.style.display = "none";
        clearReportForm();
      } else {
        showReportError(
          data.message || "An error occurred while submitting your report."
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showReportError(
        "An error occurred while submitting your report. Please try again."
      );
    })
    .finally(() => {
      // Re-enable submit button
      submitButton.disabled = false;
      submitButton.classList.remove("loading");
    });
}

// Initialize report system when page loads
document.addEventListener("DOMContentLoaded", initializeReportSystem);

// Post Management Functions
function confirmDelete() {
  document.getElementById("deleteModal").style.display = "block";
}

function closeModal() {
  document.getElementById("deleteModal").style.display = "none";
}

function deletePost() {
  fetch("delete_post.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "postID=" + postID,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.location.href = "user-dashboard.php";
      } else {
        alert(data.message || "An error occurred while deleting the post.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while deleting the post.");
    });
}

function handleDownload(postID, event) {
  event.preventDefault();

  let downloadFrame = document.createElement("iframe");
  downloadFrame.style.display = "none";
  document.body.appendChild(downloadFrame);
  downloadFrame.src = `download_post.php?postID=${postID}`;

  setTimeout(() => {
    document.body.removeChild(downloadFrame);
  }, 2000);
}

// Initialize everything when the DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  // Get revealed count from sessionStorage (resets when browser session ends)
  const storedRevealCount = sessionStorage.getItem(
    `revealedQuestions_${postID}`
  );
  revealedQuestionCount = storedRevealCount ? parseInt(storedRevealCount) : 0;

  // If user is logged in, reset reveal count to total questions
  if (isLoggedIn) {
    revealedQuestionCount = guestRevealData.totalQuestions;
  }
  // Initialize navigation
  updateNavButtons();
  updatePaginationDots();

  // Initialize report system
  initializeReportSystem();

  // Initialize rating system if user is not author
  if (!isAuthor) {
    document.querySelectorAll(".star").forEach((star) => {
      star.addEventListener("click", function () {
        if (!isLoggedIn) {
          alert("Please log in to rate.");
          return;
        }

        const rating = this.dataset.rating;
        submitRating(rating);
      });
    });
  }

  // Initialize comment form
  const commentForm = document.getElementById("comment-form");
  if (commentForm) {
    commentForm.addEventListener("submit", handleCommentSubmit);
  }

  // Initialize rating modal functionality
  initializeRatingModal();
});

// Helper function to submit rating
function submitRating(rating) {
  fetch("submit_rating.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `postID=${postID}&rating=${rating}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateUserStars(rating);
        updateAverageRating(data.avgRating, data.count);
      } else {
        alert(
          data.message || "An error occurred while submitting your rating."
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while submitting your rating.");
    });
}

function initializeRatingModal() {
  const modal = document.getElementById("ratingModal");
  const userCountSpan = document.getElementById("ratingUserCount");
  const closeSpan = document.getElementsByClassName("close")[0];

  if (userCountSpan) {
    userCountSpan.onclick = function () {
      fetchRatings();
      modal.style.display = "block";
    };
  }

  if (closeSpan) {
    closeSpan.onclick = function () {
      modal.style.display = "none";
    };
  }
}
