### Stellar Skirmish API Documentation

Here is a list of all current API endpoints and a guide on how to use them to run a game from start to finish.

---

### 1. Authentication Endpoints

These endpoints handle user identification and security.

*   **`POST /api/login`**
    *   **Purpose:** Authenticate and receive a Sanctum API token.
    *   **Payload:** `{ "email": "user@example.com", "password": "password" }`
    *   **Response:** `{ "token": "..." }`
    *   **Usage:** Use the returned token in the `Authorization: Bearer {token}` header for all subsequent requests.

*   **`GET /api/user`** (Protected)
    *   **Purpose:** Fetch the currently authenticated user's profile.
    *   **Response:** User object (id, name, email, etc.).

---

### 2. Game Lifecycle Endpoints (Protected)

These endpoints manage the creation, joining, and starting of games.

*   **`GET /api/games`**
    *   **Purpose:** List games.
    *   **Query Params:**
        *   `mine=1`: Shows games you created or joined.
        *   `joinable=1`: Shows public games waiting for players.
    *   **Response:** A list of game objects.

*   **`POST /api/games`**
    *   **Purpose:** Create a new game lobby.
    *   **Payload:**
        *   `visibility`: "public" or "private" (default: "private").
        *   `player_count`: 2 (default).
        *   `seed`: Optional integer for deterministic gameplay.
    *   **Response:** The created game object. For private games, an `invite_code` is returned.

*   **`GET /api/games/{game}`**
    *   **Purpose:** Retrieve the current state of a specific game.
    *   **Behavior:** The `state` object is hidden until the game is `active`.

*   **`POST /api/games/{game}/join`**
    *   **Purpose:** Join a waiting game.
    *   **Payload:** `{ "invite_code": "ABCDEF" }` (Required if the game is private).

*   **`POST /api/games/{game}/leave`**
    *   **Purpose:** Leave a waiting game or resign from an active game.

*   **`POST /api/games/{game}/start`**
    *   **Purpose:** Transition a game from `waiting` to `active`.
    *   **Rule:** Only the game creator can call this, and exactly 2 players must be in the game.

---

### 3. Gameplay Endpoints (Protected)

*   **`POST /api/games/{game}/actions`**
    *   **Purpose:** Play a card or a mercenary.
    *   **Payload (Play Card):** `{ "type": "play_card", "card_value": 7 }`
    *   **Payload (Play Mercenary):** `{ "type": "play_mercenary", "mercenary_id": "..." }`
    *   **Response:** The updated game state.

---

### How to Make a Game Happen: Step-by-Step

To run a complete game session, follow these steps:

1.  **Authentication:**
    *   Both Player A and Player B log in via `POST /api/login` to get their tokens.

2.  **Creation (Player A):**
    *   Player A calls `POST /api/games` with `visibility: "public"`.
    *   Player A receives the `game_id`. At this point, the game status is `waiting`.

3.  **Joining (Player B):**
    *   Player B finds the game via `GET /api/games?joinable=1` or by receiving the ID/Code from Player A.
    *   Player B calls `POST /api/games/{game_id}/join`.

4.  **Starting (Player A):**
    *   Once both are in, Player A calls `POST /api/games/{game_id}/start`.
    *   The status changes to `active`, and the initial `state` (hands, deck, planets) is generated and returned.

5.  **Playing (Turns):**
    *   Players inspect the `state` via `GET /api/games/{game_id}`.
    *   Players submit moves via `POST /api/games/{game_id}/actions`.
    *   The engine automatically resolves battles once both players have played their cards for a round.

6.  **Completion:**
    *   The game continues until the engine determines a winner.
    *   The status changes to `completed`, and the final `state` reflects the end-of-game results.
