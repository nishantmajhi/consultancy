<?php
    class UI {
        public static function getAsideContents(): string {
            return <<<'NOWDOC'
                        <nav>
                            <img
                            src="../../assets/img/logo.webp"
                            alt="Company Logo"
                            id="company_logo"
                            />

                            <details id="home" open>
                            <summary>Home</summary>
                            <ul>
                                <li>
                                <a
                                    href="../"
                                    class="view-existing"
                                    title="Payment and Application Status"
                                    >Summary</a
                                >
                                </li>
                                <li>
                                <a
                                    href="../interestedPeople/"
                                    class="view-existing"
                                    title="Interested People"
                                    >Potential Clients</a
                                >
                                </li>
                            </ul>
                            </details>

                            <details id="job_seekers" open>
                            <summary>Clients</summary>
                            <ul>
                                <li>
                                <a
                                    href="../listClients/"
                                    class="view-existing"
                                    title="View Clients"
                                    >List all</a
                                >
                                </li>
                                <li>
                                <a href="../addClient/" class="add-new" title="Add Client"
                                    >Add New</a
                                >
                                </li>
                            </ul>
                            </details>

                            <details id="available_jobs" open>
                            <summary>Vacancies</summary>
                            <ul>
                                <li>
                                <a href="../listJobs/" class="view-existing" title="View Vacancies"
                                    >List all</a
                                >
                                </li>
                                <li>
                                <a href="../addJob/" class="add-new" title="Add Vacancy"
                                    >Add New</a
                                >
                                </li>
                            </ul>
                            </details>

                            <details id="archived_data" open>
                            <summary>Archived</summary>
                            <ul>
                                <li>
                                <a
                                    href="../archivedClients/"
                                    class="view-existing"
                                    title="View Old Clients"
                                    >Clients</a
                                >
                                </li>
                                <li>
                                <a
                                    href="../archivedJobs/"
                                    class="view-existing"
                                    title="View Old Jobs"
                                    >Vacancies</a
                                >
                                </li>
                            </ul>
                            </details>
                        </nav>
                        <button type="button" id="logout_button">Logout</button>
                    NOWDOC;
        }

        public static function getHeaderContents(string $heading): string {
            return <<<HEREDOC
                        <span>$heading</span>
                        <span id="notification_container" title="View Notifications">
                        <button
                            type="button"
                            id="notification_bell"
                            aria-label="Notification Icon"
                        ></button>
                        <section id="notifications_menu"></section>
                        <div class="notification-overlay"></div>
                        </span>
                    HEREDOC;
        }
    }