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

            if (!(typeof hex_hmac_md5 === 'function')) throw "Required hashing function not defined";
            if (!this.form.querySelector("INPUT[name='email']")) throw "Required field 'email' not found";
            if (!this.form.querySelector("INPUT[name='password']")) throw "Required field 'password' not found";

            //do not check the rand as this class is reused from the registerform
            // if (!this.form.querySelector("INPUT[type='hidden'][name='rand']")) throw "Required field 'rand' not found";

            if (!this.form.querySelector("INPUT[type='hidden'][name='pass']")) throw "Required field 'pass' not found";

            this.form.addEventListener("submit", (event) => this.onSubmit(event));


        } catch (exx) {
            alert("Unable to attach with requested Form: " + exx + " " + this.selector());
        }

    }

    async onSubmit(event) {

        try {

            await this.processEmail();
            await this.processPassword();
            return;

        } catch (exx) {
            showAlert(exx);
        }

        event.preventDefault(); // prevent the default form submission behavior
    }

    async processPassword() {

        this.form.password.value = this.form.password.value.trim();

        if (this.form.password.value.length < 1) throw "Input your password to continue.";
        if (this.form.password.value.length < 6) throw "Minimum password of 6 symbols required.";

        this.form.pass.value = hex_md5(this.form.password.value);

        //login forms have rand
        if (this.form.rand) {
            let rand = this.form.rand.value;
            this.form.pass.value = hex_hmac_md5(this.form.pass.value, rand);
            this.form.rand.value = "";
        }

        this.form.password.value = "";

    }

    async hmac(hmac_key, hmac_message) {
        // encoder to convert string to Uint8Array
        var enc = new TextEncoder("utf-8");

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
        const signature =  await  window.crypto.subtle.sign(
            "HMAC",
            key,
            enc.encode(hmac_message)
        );

        let signature_uint8 = new Uint8Array(signature);
        //hex
        return Array.prototype.map.call(signature_uint8, x => x.toString(16).padStart(2, '0')).join("");

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
