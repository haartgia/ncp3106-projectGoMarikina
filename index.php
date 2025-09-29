<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GO! MARIKINA</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

<div class="container">
    <main id="main-content" class="main-content">
        <div class="hero-section">

            <div class="header-pattern-top">
                <img src="./uploads/header.png" alt="Top Decorative Header Pattern">
            </div>
            
            <div class="top-right-logo">
                <img src="./uploads/marikina_logo.png" alt="Marikina Logo">
            </div>
            
            <div class="content-container">
                <div class="left-building">
                    <img src="./uploads/l_building.png" alt="Left Building">
                </div>
                <div class="right-building">
                    <img src="./uploads/r_build.png" alt="Right Building">
                </div>
                
                <div class="hero-content">

                    <div class="main-logo">
                        <img class="ph-logo" src="./uploads/ph_logo.png" alt="Philippines Logo Background">
                        <img class="go-logo" src="./uploads/go_marikina_logo.png" alt="GO! MARIKINA Logo">
                    </div>
                    
                    <button class="create-report-btn" onclick="openCreateReport()">Create a Report</button>
                </div>
            </div>
        </div>

            <!-- Reports Section -->
            <div id="reports" class="reports-section">
                <div class="reports-header">
                    <button class="current-reports-btn">Current Reports</button>
                    <h2 class="reports-date">
                        <?php 
                            // Set timezone to Philippines
                            date_default_timezone_set('Asia/Manila');
                            // Display current day and date in uppercase
                            echo strtoupper(date('l, F j, Y'));
                        ?>
                    </h2>
                    <div class="reports-filter-control">
                        <button class="filter-btn" type="button" aria-haspopup="true" aria-expanded="false" data-default-label="Filter">Filter</button>
                        <div class="filter-dropdown" role="menu" hidden>
                            <p class="filter-dropdown-title">Show</p>
                            <button type="button" class="filter-option active" role="menuitemradio" aria-checked="true" data-filter="all" data-label="All Reports">All Reports</button>
                            <button type="button" class="filter-option" role="menuitemradio" aria-checked="false" data-filter="unresolved" data-label="Unresolved">Unresolved</button>
                            <button type="button" class="filter-option" role="menuitemradio" aria-checked="false" data-filter="solved" data-label="Solved">Solved</button>
                        </div>
                    </div>
                </div>
                
                <!-- Reports Grid -->
                <div class="reports-grid">
                <div class="report-card">
                    <img src="uploads/road-construction.png" alt="Road construction">
                    <div class="report-info">
                        <div class="report-header">
                            <div class="report-category">COMMUNITY</div>
                            <div class="report-status unresolved">UNRESOLVED</div>
                        </div>
                        <div class="report-description">The road construction at Bulelak Street has been dragging on for weeks, causing daily inconvenience to residents and commuters alike. Traffic has become unbearable, with vehicles piling up during rush hours and people struggling to get in and out of the area. Small businesses along the street are also suffering because customers avoid passing through the congested road. Despite the long wait and the visible frustration of the community, there are still no clear updates on when the project will be completed, leaving everyone anxious and frustrated about how much longer they will have to endure the disruption.</div>
                        <div class="report-footer">
                            <div class="report-meta">
                                <span>5 mins ago, September 15</span>
                                <span>Miguel De Guzman</span>
                            </div>
                            <button class="see-more-btn" onclick="openReportModal(1)">See More</button>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <img src="uploads/flooding.png" alt="Flooding">
                    <div class="report-info">
                        <div class="report-header">
                            <div class="report-category">COMMUNITY</div>
                            <div class="report-status unresolved">UNRESOLVED</div>
                        </div>
                        <div class="report-description">Flooding along Sumulong has become a serious problem that affects not only the daily lives of residents but also the overall safety and economic activity in the area. Every time heavy rain pours, the streets quickly become submerged, making it nearly impossible for vehicles to pass through and forcing pedestrians to wade through dangerous, waist-deep waters. The constant flooding damages homes, weakens road infrastructure, and disrupts transportation, leaving commuters stranded and delaying essential services. Small businesses suffer significant losses because customers avoid flooded areas, deliveries are delayed, and property is repeatedly damaged. On top of that, the stagnant water poses health risks, becoming a breeding ground for mosquitoes and raising concerns about waterborne diseases.</div>
                        <div class="report-footer">
                            <div class="report-meta">
                                <span>5 mins ago, September 15</span>
                                <span>Miguel De Guzman</span>
                            </div>
                            <button class="see-more-btn" onclick="openReportModal(2)">See More</button>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <img src="uploads/no-parking.png" alt="No parking">
                    <div class="report-info">
                        <div class="report-header">
                            <div class="report-category">COMMUNITY</div>
                            <div class="report-status solved">Solved</div>
                        </div>
                        <div class="report-description">Grabe na ‘yung illegal parking dito sa area halos kalahati ng kalsada ay okupado na ng mga sasakyan na naka park kung saan-saan. Dahil dito, sobrang sikip na ng daan at hirap na hirap na ang mga dumadaan, lalo na kapag rush hour. Imbes na dalawang lane ang magagamit ng mga motorista, isa na lang talaga ang bukas dahil yung kabila, halos naging extension na ng parking lot. Ang masama pa, hindi lang mga private cars kundi pati ilang delivery trucks at jeepney drivers na naghihintay ng pasahero ang humaharang, kaya lalo pang nagkaka-abala.</div>
                        <div class="report-footer">
                            <div class="report-meta">
                                <span>5 mins ago, September 15</span>
                                <span>Miguel De Guzman</span>
                            </div>
                            <button class="see-more-btn" onclick="openReportModal(3)">See More</button>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <img src="uploads/no-parking.png" alt="No parking">
                    <div class="report-info">
                        <div class="report-header">
                            <div class="report-category">COMMUNITY</div>
                            <div class="report-status solved">Solved</div>
                        </div>
                        <div class="report-description">Grabe na ‘yung illegal parking dito sa area halos kalahati ng kalsada ay okupado na ng mga sasakyan na naka park kung saan-saan. Dahil dito, sobrang sikip na ng daan at hirap na hirap na ang mga dumadaan, lalo na kapag rush hour. Imbes na dalawang lane ang magagamit ng mga motorista, isa na lang talaga ang bukas dahil yung kabila, halos naging extension na ng parking lot. Ang masama pa, hindi lang mga private cars kundi pati ilang delivery trucks at jeepney drivers na naghihintay ng pasahero ang humaharang, kaya lalo pang nagkaka-abala.</div>
                        <div class="report-footer">
                            <div class="report-meta">
                                <span>5 mins ago, September 15</span>
                                <span>Miguel De Guzman</span>
                            </div>
                            <button class="see-more-btn" onclick="openReportModal(3)">See More</button>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <img src="uploads/flooding.png" alt="Flooding">
                    <div class="report-info">
                        <div class="report-header">
                            <div class="report-category">COMMUNITY</div>
                            <div class="report-status unresolved">UNRESOLVED</div>
                        </div>
                        <div class="report-description">Flooding along Sumulong has become a serious problem that affects not only the daily lives of residents but also the overall safety and economic activity in the area. Every time heavy rain pours, the streets quickly become submerged, making it nearly impossible for vehicles to pass through and forcing pedestrians to wade through dangerous, waist-deep waters. The constant flooding damages homes, weakens road infrastructure, and disrupts transportation, leaving commuters stranded and delaying essential services. Small businesses suffer significant losses because customers avoid flooded areas, deliveries are delayed, and property is repeatedly damaged. On top of that, the stagnant water poses health risks, becoming a breeding ground for mosquitoes and raising concerns about waterborne diseases.</div>
                        <div class="report-footer">
                            <div class="report-meta">
                                <span>5 mins ago, September 15</span>
                                <span>Miguel De Guzman</span>
                            </div>
                            <button class="see-more-btn" onclick="openReportModal(2)">See More</button>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <img src="uploads/road-construction.png" alt="Road construction">
                    <div class="report-info">
                        <div class="report-header">
                            <div class="report-category">COMMUNITY</div>
                            <div class="report-status unresolved">UNRESOLVED</div>
                        </div>
                        <div class="report-description">The road construction at Bulelak Street has been dragging on for weeks, causing daily inconvenience to residents and commuters alike. Traffic has become unbearable, with vehicles piling up during rush hours and people struggling to get in and out of the area. Small businesses along the street are also suffering because customers avoid passing through the congested road. Despite the long wait and the visible frustration of the community, there are still no clear updates on when the project will be completed, leaving everyone anxious and frustrated about how much longer they will have to endure the disruption.</div>
                        <div class="report-footer">
                            <div class="report-meta">
                                <span>5 mins ago, September 15</span>
                                <span>Miguel De Guzman</span>
                            </div>
                            <button class="see-more-btn" onclick="openReportModal(1)">See More</button>
                        </div>
                    </div>
                    
            </div>
            </div>
        </div>
    </main>
</div>

<!-- Report Detail Modal -->
<div id="reportModal" class="modal">
    <div class="modal-content report-modal">
        <span class="close" onclick="closeModal('reportModal')">&times;</span>
        <div class="report-date">MONDAY, SEPTEMBER 15, 2025</div>
        <div class="report-detail">
            <img id="reportImage" src="/placeholder.svg" alt="Report image">
            <div class="report-detail-info">
                <div class="report-status-detail">STATUS: <span id="reportStatus"></span></div>
                <div class="report-posted">POSTED BY: <span id="reportAuthor"></span></div>
                <div class="report-description-detail">
                    <strong>DESCRIPTION:</strong>
                    <p id="reportDescription"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './includes/navbar.php'; ?>
<script src="./assets/js/script.js"></script>
</body>
</html>