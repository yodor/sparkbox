/**
 * The script of the OptionFinder component (lib/components/OptionFinder.php): reads the options once from the
 * JSON source, lists the ones matching the typed text, a click (or Enter) chooses one - its key goes to the hidden
 * field and the chosen option is shown. Options: {key, title, text, prefix} - prefix (lower case) is matched from
 * its start and listed first, then title and text anywhere.
 */
class OptionFinder extends Component {

    static MAX_SHOWN = 60;

    /**
     * @param name {string}
     * @returns {OptionFinder}
     */
    static Start(name) {
        const finder = new OptionFinder();
        finder.setName(name);
        finder.initialize();
        return finder;
    }

    constructor() {
        super();
        this.class = ".OptionFinder";
        this.options = null;
        this.loading = null;
        this.active = -1;
    }

    initialize() {
        super.initialize();
        this.query = this.element.querySelector(".query");
        this.value = this.element.querySelector("input[type='hidden']");
        this.chosen = this.element.querySelector(".chosen");
        this.list = this.element.querySelector(".options");
        this.hint = this.element.querySelector(".hint");

        this.query.addEventListener("focus", () => this.load());
        this.query.addEventListener("input", () => this.load().then(() => this.filter()));
        this.query.addEventListener("keydown", (event) => this.onKey(event));
        this.showHint(this.element.getAttribute("hint-empty"));
    }

    /**
     * The options from the source - once
     * @returns {Promise<void>}
     */
    load() {
        if (this.loading) return this.loading;
        this.loading = fetch(this.element.getAttribute("source"), {credentials: "same-origin"})
            .then((response) => response.json())
            .then((json) => {
                this.options = (json && json.status === "OK") ? JSON.parse(json.contents || "[]") : [];
                this.options.forEach((option) => option.search = ((option.title || "") + " " + (option.text || "")).toLowerCase());
            })
            .catch((error) => {
                console.log(error);
                this.options = [];
            });
        return this.loading;
    }

    filter() {
        const typed = this.query.value.trim().toLowerCase();
        this.list.replaceChildren();
        this.active = -1;
        if (typed.length < 1 || !this.options) {
            this.showHint(this.element.getAttribute("hint-empty"));
            return;
        }
        const first = [], then = [];
        for (const option of this.options) {
            if ((option.prefix || "").startsWith(typed)) first.push(option);
            else if ((option.search || "").includes(typed)) then.push(option);
            if (first.length >= OptionFinder.MAX_SHOWN) break;
        }
        const shown = first.concat(then).slice(0, OptionFinder.MAX_SHOWN);
        shown.forEach((option) => this.list.appendChild(this.item(option, typed)));
        this.showHint(shown.length ? "" : this.element.getAttribute("hint-none"));
    }

    /**
     * @param option {Object}
     * @param typed {string}
     * @returns {HTMLLIElement}
     */
    item(option, typed) {
        const li = document.createElement("li");
        li.className = "option";
        li.setAttribute("role", "option");
        if (option.key === this.value.value) li.classList.add("selected");
        li.appendChild(this.marked("title", option.title || "", typed));
        li.appendChild(this.marked("text", option.text || "", typed));
        li.addEventListener("click", () => this.choose(option));
        return li;
    }

    /**
     * A div with the typed text underlined (text nodes only - no HTML of the options)
     */
    marked(className, value, typed) {
        const div = document.createElement("div");
        div.className = className;
        const at = value.toLowerCase().indexOf(typed);
        if (at < 0) {
            div.textContent = value;
            return div;
        }
        div.appendChild(document.createTextNode(value.substring(0, at)));
        const u = document.createElement("u");
        u.textContent = value.substring(at, at + typed.length);
        div.appendChild(u);
        div.appendChild(document.createTextNode(value.substring(at + typed.length)));
        return div;
    }

    choose(option) {
        this.value.value = option.key;
        this.chosen.querySelector(".title").textContent = option.title || "";
        this.chosen.querySelector(".text").textContent = option.text || "";
        this.chosen.hidden = false;
        this.list.querySelectorAll(".option").forEach((li) => li.classList.remove("selected"));
        this.list.replaceChildren();
        this.showHint("");
        this.value.dispatchEvent(new Event("change", {bubbles: true}));
    }

    onKey(event) {
        const items = [...this.list.querySelectorAll(".option")];
        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            if (!items.length) return;
            event.preventDefault();
            this.active = (event.key === "ArrowDown") ? Math.min(this.active + 1, items.length - 1) : Math.max(this.active - 1, 0);
            items.forEach((li, index) => li.classList.toggle("active", index === this.active));
            items[this.active].scrollIntoView({block: "nearest"});
        }
        else if (event.key === "Enter") {
            //Enter chooses the active option - never submits the form from the search field
            event.preventDefault();
            if (this.active >= 0 && items[this.active]) items[this.active].click();
            else if (items.length === 1) items[0].click();
        }
    }

    showHint(text) {
        this.hint.textContent = text || "";
        this.hint.hidden = !text;
    }
}
