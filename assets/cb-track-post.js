//using jQuery to track post views
jQuery(document).ready(function ($) {
  // Cookie helper function
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  function initializeTracking() {
    // First, clear all previously applied styles
    $(".e-loop-item").css({
      backgroundColor: "",
      borderRadius: "none",
    });

    // get the post id from cookie and console
    var postId = getCookie("cb_post_id");

    //find the element with the class 'e-loop-item e-loop-item-{PostId}' and apply style
    var postElement = $(".e-loop-item.e-loop-item-" + postId);
    if (postElement.length) {
      postElement.css({
        backgroundColor: "#505C8A",
        borderRadius: "50%",
      });
    } else {
      console.log("CB Post Tracker: Post element not set");
    }
  }

  // Run on initial page load
  initializeTracking();

  // Handle back/forward navigation and page show events
  window.addEventListener("pageshow", function (event) {
    // Check if the page is loaded from cache (back/forward navigation)
    if (
      event.persisted ||
      (window.performance && window.performance.navigation.type === 2)
    ) {
      initializeTracking();
    }
  });
});
