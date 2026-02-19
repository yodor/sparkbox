<?php
enum GTMConvParam : string
{
    case CART_ADD = "conv_cart_add";
    case PHONE_CALL = "conv_phone_call";
    case FAST_ORDER = "conv_fast_order";
    case QUERY_PRODUCT = "conv_query_product";
    case CONTACT_REQUEST = "conv_contact_request";

    case VIEW_PDP = "conv_view_pdp";
    case VIEW_PLP = "conv_view_plp";

    case VIEW_ANY_PAGE = "conv_view_any_page";

}