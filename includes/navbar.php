<div class="navbar-container">
  <!-- Navigation Links -->
  <div class="navbar-wrapper">
    <nav class="navbar-menu">
      <a href="index.php#main-content">HOME</a>
      <a href="index.php#reports">REPORTS</a>
      <button type="button" class="nav-search-trigger">SEARCH</button>
      <a href="profile.php">PROFILE</a>
    </nav>
  </div>
  <div class="navbar-search-overlay" aria-hidden="true">
    <form class="navbar-search-form" action="search.php" method="get">
      <label class="visually-hidden" for="navbarSearchInput"></label>
      <div class="navbar-search-inner">
        <span class="navbar-search-icon" aria-hidden="true"></span>
        <input id="navbarSearchInput" type="text" name="q" placeholder="Search for reports, date, and status" autocomplete="off">
        <button type="button" class="navbar-search-close" aria-label="Close search">Cancel</button>
      </div>
    </form>
  </div>
</div>
