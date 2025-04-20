//using jQuery to track post views
jQuery(document).ready(function ($) {
  // Cookie helper function
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  // Add this helper function at the top with your other functions
  function getBookmarkedPosts() {
    const bookmarkedPosts = getCookie("bookmarked_posts");
    return bookmarkedPosts ? JSON.parse(bookmarkedPosts) : [];
  }

  // Add this helper function to save bookmarked posts
  function saveBookmarkedPosts(posts) {
    document.cookie = `bookmarked_posts=${JSON.stringify(
      posts
    )}; path=/; max-age=${30 * 24 * 60 * 60}`; // 30 days expiry
  }

  function initializeTracking() {
    // First, clear all previously applied styles
    $(".e-loop-item").css({
      backgroundColor: "",
      borderRadius: "none",
      cursor: "pointer",
    });

    // get the post id from cookie and console
    var postId = getCookie("cb_post_id");

    console.log("Post ID from cookie: " + postId);

    //find the element with the class 'e-loop-item e-loop-item-{PostId}' and apply style
    var postElement = $(".e-loop-item.e-loop-item-" + postId);
    if (postElement.length) {
      postElement.css({
        backgroundColor: "#505C8A",
        borderRadius: "50%",
        cursor: "pointer",
      });
    }

    //check if the postId includes in the bookmarked_posts cookie then add the class 'bookmarked' to the button
    var bookmarkedPosts = getBookmarkedPosts();
    if (bookmarkedPosts.includes(postId)) {
      $(".cb-bookmark-btn").addClass("bookmarked");
    } else {
      $(".cb-bookmark-btn").removeClass("bookmarked");
    }

    $(".cb-bookmark-btn").on("click", function () {
      var postId = getCookie("cb_post_id");
      if (postId) {
        let bookmarkedPosts = getBookmarkedPosts();

        // Check if post is already bookmarked
        const index = bookmarkedPosts.indexOf(postId);

        if (index === -1) {
          // Add post to bookmarks
          bookmarkedPosts.push(postId);
          console.log("Post bookmarked: " + postId);
          $(this).addClass("bookmarked");
        }
        // Remove post from bookmarks
        else {
          bookmarkedPosts.splice(index, 1);
          console.log("Post unbookmarked: " + postId);
          $(this).removeClass("bookmarked");
        }
        saveBookmarkedPosts(bookmarkedPosts);
      }
    });
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
