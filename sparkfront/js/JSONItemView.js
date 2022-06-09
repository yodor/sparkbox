function loadMoreResults(button_source)
{
    let itemView = $(button_source).parents(".JSONItemView").first();
    let viewPort = itemView.children(".viewport").first();

    //currentPage = index
    //pagesTotal = count
    let currentPage = parseInt(itemView.attr("page")) ;
    let pagesTotal = parseInt(itemView.attr("pagesTotal"));

    if (isNaN(currentPage)) currentPage = 0;

    if (currentPage < pagesTotal) {
        currentPage++;

        let request = new JSONRequest();
        request.setResponder("ItemViewResponder");
        request.setFunction("renderItems");
        request.setParameter("page", currentPage);

        request.onSuccess = function (result) {

            viewPort.append(result.json_result.html);
            itemView.attr("page", currentPage);
            $(button_source).removeAttr("working", "");

            let url = new URL(document.location.href);
            url.searchParams.set("page", currentPage);
            window.history.pushState({}, "", url.href);

        };
        $(button_source).attr("working", "");

        request.start();
    }
    if (currentPage == pagesTotal-1) {
        $(button_source).remove();
    }


}