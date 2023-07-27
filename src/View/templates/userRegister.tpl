<!doctype html>
<form>
    <div>
        <h1>Register</h1>
        <p>Kindly fill in this form to register.</p>
        <!-- label for username -->
        <label for="username"><b>Username</b></label>
        <!-- input field for username -->
        <input
                type="text"
                placeholder="Enter username."
                name="username"
                id="username"
                required
        />
        <label for="password"><b>Password</b></label>
        <input
                type="password"
                placeholder="Enter password"
                name="password"
                id="password"
                required
        />
        <label for="firstname"><b>First name</b></label>
        <input
                type="text"
                placeholder="Enter firstname"
                name="firstname"
                id="firstname"
                required
        />
        <label for="lastname"><b>Last name</b></label>
        <input
                type="text"
                placeholder="Enter lastname"
                name="lastname"
                id="lastname"
                required
        />
        <label for="gender"><b>Gender</b></label>
        <input
                type="text"
                placeholder="Enter gender"
                name="gender"
                id="gender"
                required
        />
        <button type="submit">Register</button>
    </div>
    <div></div>
</form>
</html>