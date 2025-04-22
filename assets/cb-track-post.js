class PostTracker {
  constructor() {
    this.$ = jQuery;
    this.initialize();
  }

  initialize() {
    this.initializeEventListeners();
    this.checkPostIdFromBodyClass(); // Check post ID from body class first
    this.initializeTracking(); // Explicitly call on construction
  }

  getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  getBookmarkedPosts() {
    const bookmarkedPosts = this.getCookie("cb_bookmarked_posts");
    return bookmarkedPosts ? JSON.parse(bookmarkedPosts) : [];
  }

  saveBookmarkedPosts(posts) {
    document.cookie = `cb_bookmarked_posts=${JSON.stringify(
      posts
    )}; path=/; max-age=${30 * 24 * 60 * 60}`; // 30 days expiry
  }

  initializeTracking() {
    // Remove any existing viewed classes
    this.$(".e-loop-item").removeClass("viewed");
    const postId = this.getCookie("cb_post_id");

    if (!postId) {
      return; // Exit if no post ID is found
    }

    // Find and add viewed class to the post element
    const postElements = this.$(".e-loop-item").filter((_, element) => {
      return this.$(element).hasClass(`e-loop-item-${postId}`);
    });

    if (postElements.length) {
      postElements.addClass("viewed");
    }

    // Update bookmark button state
    const bookmarkedPosts = this.getBookmarkedPosts();
    const bookmarkBtn = this.$(".cb-bookmark-btn");
    if (bookmarkBtn.length) {
      bookmarkedPosts.includes(postId)
        ? bookmarkBtn.addClass("bookmarked")
        : bookmarkBtn.removeClass("bookmarked");
    }
  }

  checkPostIdFromBodyClass() {
    const bodyClasses = document.body.className.split(" ");
    const postIdClass = bodyClasses.find((className) =>
      className.startsWith("postid-")
    );

    if (postIdClass) {
      const currentPostId = postIdClass.replace("postid-", "");
      const cookiePostId = this.getCookie("cb_post_id");

      // Update cookie if post ID from body class doesn't match cookie
      if (currentPostId !== cookiePostId) {
        console.log("Updating cookie with new post ID: " + currentPostId);
        document.cookie = `cb_post_id=${currentPostId}; path=/; max-age=${
          30 * 24 * 60 * 60
        }`; // 30 days expiry
      }
    }
  }

  handleBookmarkClick() {
    const postId = this.getCookie("cb_post_id");
    if (!postId) return;

    let bookmarkedPosts = this.getBookmarkedPosts();
    const index = bookmarkedPosts.indexOf(postId);
    const bookmarkBtn = this.$(".cb-bookmark-btn");

    if (index === -1) {
      // Add post to bookmarks
      bookmarkedPosts.push(postId);
      console.log("Post bookmarked: " + postId);
      bookmarkBtn.addClass("bookmarked");
    } else {
      // Remove post from bookmarks
      bookmarkedPosts.splice(index, 1);
      console.log("Post unbookmarked: " + postId);
      bookmarkBtn.removeClass("bookmarked");
    }

    this.saveBookmarkedPosts(bookmarkedPosts);
  }

  initializeEventListeners() {
    // Handle bookmark button clicks
    this.$(".cb-bookmark-btn").on("click", () => this.handleBookmarkClick());

    // Handle bookmark remove button clicks
    this.$(".cb-bookmarked-posts").on("click", ".bookmark-remove", (e) => {
      const button = this.$(e.currentTarget);
      const postId = button.data("post-id").toString();
      const bookmarkItem = button.closest(".bookmark-item");
      const bookmarkCategory = bookmarkItem.closest(".bookmark-category");
      const bookmarksContainer = this.$(".cb-bookmarked-posts");

      let bookmarkedPosts = this.getBookmarkedPosts();
      const index = bookmarkedPosts.indexOf(postId);

      if (index !== -1) {
        // Remove from cookie first
        bookmarkedPosts.splice(index, 1);
        this.saveBookmarkedPosts(bookmarkedPosts);

        // Count remaining visible items (excluding the one being removed)
        const remainingItems = this.$(".bookmark-item:visible").length - 1;

        bookmarkItem.fadeOut(300, () => {
          bookmarkItem.remove();

          // If this was the last item in the category, remove the category div
          const categoryItemsCount = bookmarkCategory.find(
            ".bookmark-item:visible"
          ).length;
          if (categoryItemsCount === 0) {
            bookmarkCategory.fadeOut(300, () => bookmarkCategory.remove());
          }

          // Show empty message if no items remain
          if (remainingItems === 0) {
            const emptyMessage =
              "<p>No Bookmarks. <br>Tap heart to leave chapter bookmarks.</p>";
            if (bookmarksContainer.find("p").length === 0) {
              bookmarksContainer.html(emptyMessage);
            }
          }
        });
      }
    });

    // Handle back/forward navigation and page show events
    window.addEventListener("pageshow", (event) => {
      if (
        event.persisted ||
        (window.performance && window.performance.navigation.type === 2)
      ) {
        this.initializeTracking();
      }
    });

    // Add DOMContentLoaded listener for better initialization
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () =>
        this.initializeTracking()
      );
    }
  }
}

// Initialize the tracker when the document is ready
jQuery(document).ready(() => {
  new PostTracker();
});
