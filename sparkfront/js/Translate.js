/**
 * The text 'text' (English, the key of the language packs) in the language of the page - the translations the page
 * gives its scripts (HTMLHead::addTexts - document.Spark.texts); the text itself without one. With 'values' the
 * text is a format: each %s takes the next value - tr("Select an option for %s", name).
 * @param text {string}
 * @param values {...*}
 * @returns {string}
 */
function tr(text, ...values)
{
    const texts = (document.Spark && document.Spark.texts) || {};
    let result = (typeof texts[text] === "string") ? texts[text] : text;
    if (values.length > 0) {
        let index = 0;
        result = result.replace(/%s/g, () => (index < values.length) ? String(values[index++]) : "");
    }
    return result;
}
