// Basketball Spy JavaScript

function initBasketballSpy() {
    console.log('Basketball Spy loaded');
    
    // Load team names and logos on page load
    if (typeof appData !== 'undefined' && appData.teams) {
        loadTeams();
    }
    
    // Update active panel classes on initial load
    updateActivePanelClasses();
    
    // Add click handlers for team buttons
    const homeTeamButton = document.getElementById('home-team-button');
    const awayTeamButton = document.getElementById('away-team-button');
    
    if (homeTeamButton) {
        homeTeamButton.addEventListener('click', function() {
            const homeTeam = document.querySelector('.home-team');
            const awayTeam = document.querySelector('.away-team');
            if (homeTeam) {
                homeTeam.classList.add('active');
            }
            if (awayTeam) {
                awayTeam.classList.remove('active');
            }
        });
    }
    
    if (awayTeamButton) {
        awayTeamButton.addEventListener('click', function() {
            const homeTeam = document.querySelector('.home-team');
            const awayTeam = document.querySelector('.away-team');
            if (awayTeam) {
                awayTeam.classList.add('active');
            }
            if (homeTeam) {
                homeTeam.classList.remove('active');
            }
        });
    }
    
    // Option 2: Let user select a JSON file (works without server)
    const fileInput = document.getElementById('json-file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                readJSONFile(file)
                    .then(data => {
                        console.log('Loaded JSON from file:', data);
                        // Use the data here
                    })
                    .catch(error => {
                        console.error('Error reading JSON file:', error);
                    });
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBasketballSpy);
} else {
    initBasketballSpy();
}

/**
 * Load team names and logos into the HTML
 */
function loadTeams() {
    // Load in-game lists in the team panels
    const teamElements = document.querySelectorAll('.team');
    console.log('loadTeams: Found', teamElements.length, 'team elements');
    teamElements.forEach(function(teamElement) {
        const teamId = parseInt(teamElement.getAttribute('data-team-id'));
        console.log('loadTeams: Processing teamId', teamId);
        const team = appData.teams[teamId];
        console.log('loadTeams: Team object:', team);
        
        if (team) {
            console.log('loadTeams: Team nickname:', team.nickname);
            // Load team header in panel
            const teamHeaderPanel = teamElement.querySelector('.team-header-panel');
            console.log('loadTeams: teamHeaderPanel found:', !!teamHeaderPanel);
            if (teamHeaderPanel) {
                const nameElement = teamHeaderPanel.querySelector('.team-name-panel');
                console.log('loadTeams: nameElement found:', !!nameElement);
                if (nameElement) {
                    console.log('loadTeams: Setting textContent to:', team.nickname);
                    nameElement.textContent = team.nickname;
                    console.log('loadTeams: nameElement.textContent after setting:', nameElement.textContent);
                } else {
                    console.log('loadTeams: nameElement not found!');
                }
            } else {
                console.log('loadTeams: teamHeaderPanel not found!');
            }
            
            // Load logo into team button
            const teamButtonId = teamId === 0 ? 'home-team-button' : 'away-team-button';
            const teamButton = document.getElementById(teamButtonId);
            if (teamButton) {
                const logoElement = teamButton.querySelector('.team-button__logo');
                if (logoElement) {
                    logoElement.src = team.logoUrl;
                    logoElement.alt = team.name + ' logo';
                }
            }
            
            // Load in-game
            loadInGame(teamElement, team);
        } else {
            console.log('loadTeams: Team not found for teamId', teamId);
        }
    });
    
    // Enable transitions after a brief delay to prevent initial slide animation
    setTimeout(function() {
        const activeElements = document.querySelectorAll('.active');
        activeElements.forEach(function(activeElement) {
            activeElement.classList.remove('no-transition');
        });
    }, 100);
}

/**
 * Show player in the edit panel
 * @param {Object} player - The player object to display
 * @param {HTMLElement} teamElement - The team container element the player belongs to
 */
function showPlayerInEditPanel(player, teamElement) {
    if (!player || !teamElement) {
        return;
    }
    
    // Find the player-edit div for this team
    const playerEditDiv = teamElement.querySelector('.player-edit');
    if (!playerEditDiv) {
        return;
    }
    
    // Get the template
    const template = document.querySelector('.player-edit-template');
    if (!template) {
        return;
    }
    
    // Clear any existing content
    playerEditDiv.innerHTML = '';
    
    // Clone and append each child of the template directly to the player-edit div
    Array.from(template.children).forEach(function(child) {
        const clonedChild = child.cloneNode(true);
        playerEditDiv.appendChild(clonedChild);
    });
    
    // Get elements from the cloned content
    const editCard = playerEditDiv.querySelector('.player-card--edit');
    const headshotElement = editCard ? editCard.querySelector('.player-card__headshot') : null;
    const nameElement = editCard ? editCard.querySelector('.player-card__name') : null;
    const detailsElement = editCard ? editCard.querySelector('.player-card__details') : null;
    const closeButton = editCard ? editCard.querySelector('.player-card__button') : null;
    
    if (!headshotElement || !nameElement || !detailsElement) {
        return;
    }
    
    // If screen is 1024px or wider, remove editing class from the other team
    if (window.innerWidth >= 1024) {
        const allTeams = document.querySelectorAll('.team');
        allTeams.forEach(function(team) {
            if (team !== teamElement) {
                team.classList.remove('editing');
                // Also clear the edit panel of the other team
                const otherEditDiv = team.querySelector('.player-edit');
                if (otherEditDiv) {
                    otherEditDiv.innerHTML = '';
                }
            }
        });
    }
    
    // Add editing class to the team element
    teamElement.classList.add('editing');
    
    // Load player data
    headshotElement.src = player.headshotUrl;
    headshotElement.alt = player.name;
    nameElement.textContent = player.name;
    detailsElement.textContent = `#${player.jersey} • ${player.position} • ${player.height} • ${player.weight}`;
    
    // Add close button handler
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            hidePlayerEditPanel(teamElement);
        });
    }
}

/**
 * Hide the player edit panel
 * @param {HTMLElement} teamElement - The team container element
 */
function hidePlayerEditPanel(teamElement) {
    if (!teamElement) {
        return;
    }
    
    // Clear the player-edit div for this team
    const playerEditDiv = teamElement.querySelector('.player-edit');
    if (playerEditDiv) {
        playerEditDiv.innerHTML = '';
    }
    
    // Remove editing class from the team element
    teamElement.classList.remove('editing');
}

/**
 * Load in-game list for a team
 * @param {HTMLElement} teamElement - The team container element
 * @param {Object} team - The team data object
 */
function loadInGame(teamElement, team) {
    const playerList = teamElement.querySelector('.on-bench');
    if (!playerList || !team.players) {
        return;
    }
    
    // Clear any existing content
    playerList.innerHTML = '';
    
    // Sort players by jersey number
    const sortedPlayers = [...team.players].sort(function(a, b) {
        return parseInt(a.jersey) - parseInt(b.jersey);
    });
    
    // Add each player to the on-bench list in sorted order
    sortedPlayers.forEach(function(player) {
        const playerIndex = team.players.indexOf(player);
        const listItem = createPlayerListItem(player, teamElement, playerIndex, 'on-bench');
        playerList.appendChild(listItem);
    });
}

/**
 * Insert a player list item in sorted order by jersey number
 * @param {HTMLElement} list - The list element to insert into
 * @param {HTMLElement} newItem - The new list item to insert
 * @param {number} jerseyNumber - The jersey number for sorting
 */
function insertPlayerInSortedOrder(list, newItem, jerseyNumber) {
    const items = Array.from(list.children);
    const jerseyNum = parseInt(jerseyNumber);
    
    // Find the correct position
    let insertIndex = items.length;
    for (let i = 0; i < items.length; i++) {
        const itemJersey = parseInt(items[i].getAttribute('data-jersey') || '999');
        if (jerseyNum < itemJersey) {
            insertIndex = i;
            break;
        }
    }
    
    // Insert at the correct position
    if (insertIndex === items.length) {
        list.appendChild(newItem);
    } else {
        list.insertBefore(newItem, items[insertIndex]);
    }
}

/**
 * Create a player list item
 * @param {Object} player - The player data object
 * @param {HTMLElement} teamElement - The team container element
 * @param {number} playerIndex - The index of the player in the team's players array
 * @param {string} listType - Either 'on-bench', 'in-game', or 'active'
 * @returns {HTMLElement} The list item element
 */
function createPlayerListItem(player, teamElement, playerIndex, listType) {
    // Get the template
    const template = document.getElementById('player-card-template');
    if (!template) {
        console.error('Player card template not found');
        return null;
    }
    
    // Clone the template content
    const listItem = template.content.cloneNode(true).querySelector('li');
    listItem.setAttribute('data-player-index', playerIndex);
    listItem.setAttribute('data-jersey', player.jersey);
    
    // Get elements from the template
    const headshot = listItem.querySelector('.player-card__headshot');
    const playerInfo = listItem.querySelector('.player-card__info');
    const playerName = listItem.querySelector('.player-card__name');
    const playerDetails = listItem.querySelector('.player-card__details');
    const button = listItem.querySelector('.player-card__button');
    
    // Populate the template with player data
    headshot.src = player.headshotUrl;
    headshot.alt = player.name;
    playerName.textContent = player.name;
    playerDetails.textContent = `#${player.jersey} • ${player.position} • ${player.height} • ${player.weight}`;
    
    // Apply team color to in-game players
    if (listType === 'in-game') {
        const teamId = parseInt(teamElement.getAttribute('data-team-id'));
        const team = appData.teams[teamId];
        if (team && team.color) {
            listItem.style.backgroundColor = team.color;
        }
    }
    
    // Determine if this is a left or right team
    // Check for both .team and .active element classes
    const isLeftTeam = teamElement.classList.contains('home-team') || 
                       teamElement.classList.contains('left-active');
    
    // Configure button click handler based on list type
    if (listType === 'on-bench') {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the player click
            movePlayerToInGame(teamElement, playerIndex);
        });
    } else if (listType === 'in-game') {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the player click
            movePlayerToOnBench(teamElement, playerIndex);
        });
    } else {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the player click
            movePlayerToInGame(teamElement, playerIndex);
        });
    }
    
    // Add click handler to show player in edit panel
    listItem.addEventListener('click', function(e) {
        // Don't trigger if clicking the button
        if (e.target.closest('.player-card__button')) {
            return;
        }
        showPlayerInEditPanel(player, teamElement);
    });
    
    return listItem;
}

/**
 * Update body classes based on which active panels are open
 */
function updateActivePanelClasses() {
    const leftActive = document.querySelector('.left-active');
    const rightActive = document.querySelector('.right-active');
    
    if (leftActive && !leftActive.classList.contains('hidden')) {
        document.body.classList.add('left-active-open');
    } else {
        document.body.classList.remove('left-active-open');
    }
    
    if (rightActive && !rightActive.classList.contains('hidden')) {
        document.body.classList.add('right-active-open');
    } else {
        document.body.classList.remove('right-active-open');
    }
}

/**
 * Move a player from on-bench to in-game list
 * @param {HTMLElement} teamElement - The team container element
 * @param {number} playerIndex - The index of the player in the team's players array
 */
function movePlayerToInGame(teamElement, playerIndex) {
    const teamId = parseInt(teamElement.getAttribute('data-team-id'));
    const team = appData.teams[teamId];
    const player = team.players[playerIndex];
    
    // Find the on-bench list item
    const onBenchList = teamElement.querySelector('.on-bench');
    const onBenchItem = onBenchList.querySelector(`li[data-player-index="${playerIndex}"]`);
    
    if (!onBenchItem) {
        return;
    }
    
    // Find the in-game list
    const inGameList = teamElement.querySelector('.in-game');
    if (!inGameList) {
        return;
    }
    
    // Remove from on-bench list
    onBenchItem.remove();
    
    // Add to in-game list in sorted order
    const inGameItem = createPlayerListItem(player, teamElement, playerIndex, 'in-game');
    insertPlayerInSortedOrder(inGameList, inGameItem, player.jersey);
}

/**
 * Move a player from in-game to on-bench list
 * @param {HTMLElement} teamElement - The team container element
 * @param {number} playerIndex - The index of the player in the team's players array
 */
function movePlayerToOnBench(teamElement, playerIndex) {
    const teamId = parseInt(teamElement.getAttribute('data-team-id'));
    const team = appData.teams[teamId];
    const player = team.players[playerIndex];
    
    // Find the in-game list item
    const inGameList = teamElement.querySelector('.in-game');
    const inGameItem = inGameList.querySelector(`li[data-player-index="${playerIndex}"]`);
    
    if (!inGameItem) {
        return;
    }
    
    // Find the on-bench list
    const onBenchList = teamElement.querySelector('.on-bench');
    if (!onBenchList) {
        return;
    }
    
    // Remove from in-game list
    inGameItem.remove();
    
    // Add to on-bench list in sorted order
    const onBenchItem = createPlayerListItem(player, teamElement, playerIndex, 'on-bench');
    insertPlayerInSortedOrder(onBenchList, onBenchItem, player.jersey);
}


/**
 * Read JSON data embedded in HTML as a script tag
 * @param {string} elementId - ID of the script element containing JSON
 * @returns {Object|null} - Parsed JSON data or null if not found
 */
function getEmbeddedJSON(elementId) {
    const scriptElement = document.getElementById(elementId);
    if (scriptElement) {
        try {
            return JSON.parse(scriptElement.textContent);
        } catch (error) {
            console.error('Error parsing embedded JSON:', error);
            return null;
        }
    }
    return null;
}

/**
 * Read JSON from a file selected by the user
 * @param {File} file - The file object from file input
 * @returns {Promise<Object>} - Parsed JSON data
 */
function readJSONFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                resolve(data);
            } catch (error) {
                reject(new Error('Invalid JSON file: ' + error.message));
            }
        };
        reader.onerror = function() {
            reject(new Error('Error reading file'));
        };
        reader.readAsText(file);
    });
}

/**
 * Load and parse a JSON file via fetch (requires a server)
 * @param {string} filePath - Path to the JSON file
 * @returns {Promise<Object>} - Parsed JSON data
 */
async function loadJSON(filePath) {
    try {
        const response = await fetch(filePath);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to load JSON file:', error);
        throw error;
    }
}

