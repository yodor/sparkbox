class LoginForm extends Component {
    constructor() {
        super();
        this.setClass("FORM");
        this.form = null;

    }

    initialize() {

        super.initialize();

        try {
            this.form = this.getElement();

            if (!(this.form instanceof HTMLFormElement)) {
                throw "Component element is not a HTMLFormElement";
            }

            const required = ['email', 'password', 'token', 'challenge'];

            const missing = this.getMissingFields(required);
            if (missing.length === 0) {
                //console.log('All required fields are present.');
            } else {
                throw 'Missing fields:' + missing;
            }

            this.form.addEventListener("submit", (event) => this.onSubmit(event));


        } catch (exx) {
            console.log(exx);
            showAlert("Initialize failed: " + exx + " " + this.selector());
        }

    }

    /**
     * Checks whether all specified field names are present in this.formData
     *
     * @param {string[]} requiredFieldNames - Array of field names (name attributes) to verify
     * @returns {string[]}  all required fields that are missing
     */
    getMissingFields(requiredFieldNames) {

        const formData = new FormData(this.form);
        return requiredFieldNames.filter(name => !formData.has(name));
    }

    async onSubmit(event) {

        try {

            await this.processEmail();
            await this.processPassword();
            return;

        } catch (exx) {
            console.log(exx);
            showAlert(exx);
        }

        event.preventDefault(); // prevent the default form submission behavior
    }

    async processPassword() {

        const formData = new FormData(this.form);
        const password = formData.get("password");

        //store trimmed password back to the form
        this.form.password.value = password;

        if (password < 1) throw "Input your password to continue.";
        if (password < 6) throw "Minimum password of 6 symbols required.";

        const token = formData.get("token").trim();
        if (token.length!==32) throw "Token length wrong: "+ token.length;
        //console.log("Token: " + token);

        const password_digest = await this.hex_digest(password);
        //console.log("Password hex digest: " + password_digest);

        //prepare challenge using token as data and password_digest as key
        const challenge = await this.hex_hmac(password_digest, token);
        //console.log("Token hex HMAC: " + challenge);

        //store the challenge
        this.form.challenge.value = challenge;
        //clean token - not needed back
        this.form.token.value = "";


    }

    async hex_hmac(hmac_key, hmac_message) {
        // encoder to convert string to Uint8Array
        let enc = new TextEncoder("utf-8");

        const key = await window.crypto.subtle.importKey(
            "raw", // raw format of the key - should be Uint8Array
            enc.encode(hmac_key),
            { // algorithm details
                name: "HMAC",
                hash: {name: "SHA-256"}
            },
            false, // export = false
            ["sign", "verify"] // what this key can do
        );
        const signature =  await window.crypto.subtle.sign(
            "HMAC",
            key,
            enc.encode(hmac_message)
        );

        return this.toHex(signature);
    }

    async hex_digest(message) {
        let enc = new TextEncoder("utf-8");

        const hash = await window.crypto.subtle.digest(
            "SHA-256", enc.encode(message)
        );
        return this.toHex(hash)
    }

    toHex(data) {
        let data_uint8 = new Uint8Array(data);
        //hex
        return Array.prototype.map.call(data_uint8, x => x.toString(16).padStart(2, '0')).join("");
    }

    async processEmail() {
        this.form.email.value = this.form.email.value.trim();

        if (this.form.email.value.length < 1) {
            throw "Input your email";
        }
        const emailFilter = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

        if (!emailFilter.test(this.form.email.value)) {              //test email for illegal
            throw "Please enter a valid email address.";
        }

    }
}