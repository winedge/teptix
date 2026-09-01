"use strict";
var base_url = $("#base_url").val();
var cur = $("#currency").val();
$(".lds-ripple").fadeOut(1800, function () {
    $("#app").animate(
        {
            opacity: 1,
        },
        700
    );
});
$(document).ready(function () {
    var url = window.location.href;
    var id = url.substring(url.lastIndexOf("#") + 1);
    if (id == "tickets") {
        $("#tickets").load();
    }

    $(".select2").select2();

    if ($($("#date")).length) {
        $("#date").flatpickr({
            minDate: "today",
            dateFormat: "Y-m-d",
        });
    }

var proQty = $(".pro-qty");
var tax_total = parseFloat($("#tax_total").val());

var total = 0;
$(document).on("click", ".qtybtn", function () {

    var $button = $(this);
    if ($button.prop("disabled") || $button.closest(".checkout-qty-control").data("seat-map-locked") == 1) {
        return;
    }

    var dataIdValue = ($button.attr("data-id") || "").trim(); // Trim any leading/trailing spaces
    var dataIdofticket = ($button.attr("id") || "").trim();
    if (!dataIdValue || !dataIdofticket) {
        return;
    }

    var price = parseFloat($("#ticket_price"+dataIdValue).val());

    var currency_code = $("#currency_code").val();
    var cur = "$"; // Use $ for displaying prices
    var available_seat=parseInt($("#available_seat"+dataIdValue).val());
    var tpo = parseInt($("#tpo"+dataIdValue).val());
    var available = parseInt($("#available"+dataIdValue).val());

    // Debug logs to check if available_seat is being read correctly
    // console.log("Debug - dataIdValue:", dataIdValue);
    // console.log("Debug - available_seat element ID:", "available_seat"+dataIdValue);
    // console.log("Debug - available_seat value:", available_seat);
    // console.log("Debug - tpo:", tpo);
    // console.log("Debug - available:", available);

    var oldValue = $button.parent().find("input").val();
    var tickettype=$("#ticket_type"+dataIdValue).val();
    var idNumber=dataIdofticket.replace('inc-', '');


    var newVal;
    var datatickid;
    if ($button.hasClass("inc"))
    {
        // PREVIOUS CODE - Calculate max allowed based on different constraints
        // var maxAllowed = Infinity;

        // // Check ticket_per_order limit (if exists and valid)
        // if (!isNaN(tpo) && tpo > 0) {
        //     maxAllowed = Math.min(maxAllowed, tpo);
        // }

        // // Check available seats (if exists and valid)
        // if (!isNaN(available_seat) && available_seat > 0) {
        //     maxAllowed = Math.min(maxAllowed, available_seat);
        // }

        // // Check general available quantity (if exists and valid)
        // // Note: 'available' might be ticket_per_order instead of actual available quantity
        // // Only use it if it's greater than 1 or if no other constraints exist
        // if (!isNaN(available) && available > 1) {
        //     maxAllowed = Math.min(maxAllowed, available);
        // }

        // // If maxAllowed is still Infinity, set a reasonable default
        // if (maxAllowed === Infinity) {
        //     maxAllowed = 10; // Default maximum
        // }

        // console.log("Debug - maxAllowed:", maxAllowed, "oldValue:", oldValue);
        // console.log("Debug - tpo:", tpo, "available_seat:", available_seat, "available:", available);

        // if (parseInt(oldValue) < maxAllowed) {
        //     newVal = parseFloat(oldValue) + 1;
        // } else {
        //     newVal = parseFloat(oldValue);
        //     console.log("Debug - Cannot increment. Reached limit.");
        // }

        // NEW CODE - Enforce max_allowed from PHP backend (event people limit validation)
        // Example: If event.people = 1000 and total_orders = 998, then max_allowed = 2
        // User can only add 2 tickets, not 4 or more
        var maxAllowed = parseInt($("#max_allowed").val()) || Infinity;

        // ALSO Enforce ticket_per_order limit (max tickets per this specific ticket type)
        // Example: If ticket_per_order = 2, user can only select up to 2 of this specific ticket
        if (!isNaN(tpo) && tpo > 0) {
            maxAllowed = Math.min(maxAllowed, tpo);
        }

        // console.log("Debug - max_allowed from backend:", maxAllowed, "tpo:", tpo, "oldValue:", oldValue);

        if (parseInt(oldValue) < maxAllowed) {
            newVal = parseFloat(oldValue) + 1;
        } else {
            newVal = parseFloat(oldValue);
            console.log("Debug - Cannot increment. Reached limit of", maxAllowed);
            if (!isNaN(tpo) && tpo > 0 && parseInt(oldValue) >= tpo) {
                alert("Maximum " + tpo + " tickets allowed per order for this ticket type.");
            } else {
                alert("Maximum " + maxAllowed + " tickets allowed for this event.");
            }
        }

        datatickid=dataIdofticket.replace('inc-', '');
    }
    else
    {
        datatickid=dataIdofticket.replace('dec-', '');
        var checkQty=$("#"+"quantity-"+datatickid).val();
        if(checkQty==1){
            $('.removeaddonsbutton').trigger('click');
            $('.removeaddonsbutton').attr('data-check', '0');
        }
        if (oldValue > 1) {
            newVal = parseFloat(oldValue) - 1;
        } else {
            newVal = 1;
            if($button.hasClass("addons")){
                newVal = 0;
            }else{
                newVal = 1;
            }
        }
    }
    // Select all hidden inputs whose IDs start with the specified ticket ID
    const inputs = document.querySelectorAll(`input[type="hidden"][id^="${datatickid}"]`);

    // Object to store pairs of types and prices
    const dataMap = {};

    // Loop through inputs to extract type and price values dynamically
    inputs.forEach(input => {
        const id = input.id;

        // Check if the ID contains 'type' or 'price'
        const match = id.match(/^(\d+)type(\d+)$/) || id.match(/^(\d+)price(\d+)$/);

        if (match && match[1] === datatickid) {
            const index = match[2]; // The numeric index after 'type' or 'price'

            if (id.includes('type')) {
                dataMap[index] = { ...dataMap[index], type: input.value }; // Add type to the map
            }
            if (id.includes('price')) {
                dataMap[index] = { ...dataMap[index], price: parseFloat(input.value) }; // Add price to the map
            }
        }
    });

    $("#ticket_qty"+datatickid).val(newVal);
    $("#multiticketsqty"+datatickid).val(newVal);
    $('.payments input[type=radio][name="payment_type"]').prop("checked", false);
    $button.parent().find("input").val(newVal);
    $(".event-middle .qty").html(newVal);

    var tickettotalqty = 0;
    $('.totalqty').each(function() {
        tickettotalqty += parseFloat($(this).val()) || 0;  // Ensure the value is treated as a number
    });
    $("#quantity").val(tickettotalqty);
    $(".summary-qty").text(tickettotalqty);

    var per_price;
    var prevper_price;
    var taxnewamt=0;
    var ticketpricex=0;
    var grandtotalofall = $("#grandtotalofall").val();
    var grandtotalofalltax = $("#grandtotalofalltax").val();
    var totticketpriceprev = $("#totticketprice").val();
    var totticketprice=0;
    var addontax=0;
    var admin_revenuex=0;
    var org_revenuex=0;
    var newValss=newVal
    if(newVal==0){
        newVal=1;
    }
    // Now loop through the dataMap and perform the calculation where type is 'percentage'
    for (const key in dataMap) {
        var createdby = $("#"+datatickid+"createdby"+key).val();
        createdby = parseFloat(createdby );
        if (dataMap[key].type === 'percentage' && dataMap[key].price !== undefined) {
            const x = newVal * price; // Updated to reflect the current price * quantity
            per_price = parseFloat((dataMap[key].price * x) / 100);
            $("#"+datatickid+"taxval"+key).html("$ "+per_price);
            // var createdby = $("#"+datatickid+"createdby"+key).val();

            if(newVal > 1){
                var xyper_price = parseFloat((dataMap[key].price * price) / 100);
                taxnewamt=taxnewamt+xyper_price;
                ticketpricex=ticketpricex + price;
                if(createdby > 0){
                    org_revenuex=org_revenuex+xyper_price;
                }else{
                    admin_revenuex=admin_revenuex+xyper_price;
                }
            }else{
                taxnewamt=taxnewamt+per_price;
                ticketpricex=ticketpricex + price;
                if(createdby > 0){
                    org_revenuex=org_revenuex+per_price;
                }else{
                    admin_revenuex=admin_revenuex+per_price;
                }
            }

        } else if (dataMap[key].type === 'price' && dataMap[key].price !== undefined){

            per_price = parseFloat(dataMap[key].price) * newVal; // Apply the fee per ticket
            $("#"+datatickid+"taxval"+key).html("$ "+per_price);
            if(newVal > 1){
                var xyper_price = parseFloat(dataMap[key].price) * 1;
                taxnewamt=taxnewamt+xyper_price;
                ticketpricex=ticketpricex + price;
                if(createdby > 0){
                    org_revenuex=org_revenuex+xyper_price;
                }else{
                    admin_revenuex=admin_revenuex+xyper_price;
                }
            }else{
                taxnewamt=taxnewamt+per_price;
                ticketpricex=ticketpricex + price;
                if(createdby > 0){
                    org_revenuex=org_revenuex+per_price;
                }else{
                    admin_revenuex=admin_revenuex+per_price;
                }
            }

        }else{
            console.log(`Type is not percentage or price is missing for type${key}.`);
        }
    }
    var newVals=newValss;

    if(newVals != oldValue){


        var admin_revenue=$("#admin_revenue").val();
        var org_revenue=$("#org_revenue").val();


        var gtotaltax=$("#grandtotalofalltax").val();
        var addontottax=$("#"+datatickid+"taxaddoninp").val();
        if ($button.hasClass("inc")) {
            var gtotaltaxfinal=parseFloat(gtotaltax)+parseFloat(taxnewamt);
            addontottax=parseFloat(addontottax)+parseFloat(taxnewamt);
            $("#"+datatickid+"taxaddoninp").val(parseFloat(addontottax).toFixed(2));
            $("#"+datatickid+"taxaddon").html("Tax & Charges: $ "+parseFloat(addontottax).toFixed(2));
            $("#grandtotalofalltax").val(parseFloat(gtotaltaxfinal).toFixed(2));
            $(".totaltax").html("$ "+parseFloat(gtotaltaxfinal).toFixed(2));
            totticketprice=totticketprice + price;
            grandtotalofall= parseFloat(grandtotalofall) + (parseFloat(price) + parseFloat(taxnewamt));
            $("#tax_total").val(parseFloat(gtotaltaxfinal).toFixed(2));

            admin_revenue=parseFloat(admin_revenue)+parseFloat(admin_revenuex);
            org_revenue=parseFloat(org_revenue)+parseFloat(org_revenuex);
            $("#admin_revenue").val(parseFloat(admin_revenue).toFixed(2));
            $("#org_revenue").val(parseFloat(org_revenue).toFixed(2));
        }else{


            var gtotaltaxfinal=parseFloat(gtotaltax)-parseFloat(taxnewamt);
            addontottax=addontottax-taxnewamt;
            $("#"+datatickid+"taxaddoninp").val(addontottax);
            $("#"+datatickid+"taxaddon").html("Tax & Charges: $ "+parseFloat(addontottax).toFixed(2));
            $("#grandtotalofalltax").val(parseFloat(gtotaltaxfinal).toFixed(2));
            $(".totaltax").html("$ "+parseFloat(gtotaltaxfinal).toFixed(2));
            totticketprice=totticketprice - price;
            grandtotalofall= parseFloat(grandtotalofall) - (parseFloat(price) + parseFloat(taxnewamt));
            $("#tax_total").val(parseFloat(gtotaltaxfinal).toFixed(2));

            admin_revenue=parseFloat(admin_revenue)-parseFloat(admin_revenuex);
            org_revenue=parseFloat(org_revenue)-parseFloat(org_revenuex);
            $("#admin_revenue").val(parseFloat(admin_revenue).toFixed(2));
            $("#org_revenue").val(parseFloat(org_revenue).toFixed(2));
        }

            totticketprice=parseFloat(totticketprice);
            totticketprice=parseFloat(totticketprice )+ parseFloat(totticketpriceprev);
            // total = x + totalTax; // 'x' already represents price * quantity
            total = grandtotalofall;

            $("#totticketprice").val(totticketprice);
            $("#grandtotalofall").val(total);
            $(".totticketprice").text(cur+parseFloat(totticketprice).toFixed(2));
            // $(".totaltax").text(cur + parseFloat(totalTax).toFixed(2));
            $(".subtotal").text(cur + parseFloat(total).toFixed(2));
            // $(".add_ticket").val(x.toFixed(2));
            $(".event-total .total").html(cur + parseFloat(total).toFixed(2));
            $("#payment").val(parseFloat(total).toFixed(2));

            if (currency_code == "USD" || currency_code == "EUR") {
                total = total * 100; // For currencies that require conversion to smallest units
            }

            $("#stripe_payment").val(parseFloat(total).toFixed(0)); // Ensure this is an integer for Stripe
            $("#apply").prop("disabled", false);
            $(".coupon_code").removeAttr("readonly");

        $("#ticket_qty"+datatickid).val(newVal);

        $('.payments input[type=radio][name="payment_type"]').prop("checked", false);
        $button.parent().find("input").val(newVal);
        $(".event-middle .qty").html(newVal);

        var tickettotalqty = 0;
        $('.totalqty').each(function() {
            tickettotalqty += parseFloat($(this).val()) || 0;  // Ensure the value is treated as a number
        });
        $("#quantity").val(tickettotalqty);
        $(".summary-qty").text(tickettotalqty);
    }
});

$(document).on("click", "#apply", function () {
    $(".couponerror").html("");
    var p = $(".coupon_code").val();
    var is_auth = $("#is_auth").val();
    if ($("#coupon_code").val() != "" && is_auth==1) {
        var currency = $("#currency").val();
        $.ajax({
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                    "content"
                ),
            },
            url: base_url + "/applyCoupon",
            data: {
                coupon_code: $("input[name=coupon_code]").val(),
                total: $("input[name=payment]").val(),
                event_id: $("input[name=event_id]").val(),
                ticket_id: $("input[name=ticket_id]").val(),
            },

            success: function (result) {
                if (result.success == true) {
                    var stripeamount = parseFloat(result.total_price).toFixed(2) * 100;
                    $("#stripe_payment").val(stripeamount);
                    $("#payment").val(parseFloat(result.total_price).toFixed(2));
                    $(".subtotal").text(currency + parseFloat(result.total_price).toFixed(2));
                    $("#subtotal").val(parseFloat(result.total_price).toFixed(2));
                    $(".discount").text(currency + parseFloat(result.payableamount).toFixed(2));
                    $("#coupon_discount").val(parseFloat(result.discount).toFixed(2));
                    // Keep coupon input visible after first apply.
                    // Force-set the visible textbox again (some other UI handlers may re-render totals).
                    // Keep visible coupon input as coupon CODE (string) and prevent flash/clear.
                    $("#coupon_id").val(p);
                    $("#coupon_id").prop("readonly", false);
                    $("#coupon_id").css("opacity", "1");
                    $("#coupon_id")[0].focus && $("#coupon_id")[0].focus();
                    $("#coupon_id").trigger("input");
                    $("#coupon_id").trigger("change");
                    $(".coupon_id").text(p);




                    $("#apply").prop("disabled", true);
                    // Make only the visible textbox readonly (not any other coupon_code elements)
                    $("#coupon_id").attr("readonly", true);

                    if (result.coupon_type == 0) {
                        $("#discount_type").text("%" + result.discount);
                    }
                }
                if (result.success == false) {
                    $(".couponerror").html(
                        '<div class="text-danger ml-2" >' +
                            result.message +
                            "</div>"
                    );
                }
            },
        });
    }else{
        $(".couponerror").html(
            '<div class="text-danger ml-2" > You need to log in first to apply the coupon</div>'
        );
    }
});

$(document).on("click", ".btn-bio", function () {
    $(".bio-control").show(1000);
});

$(".bio-control").focusout(function () {
    var bio = this.value;
    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        type: "POST",
        url: base_url + "/add-bio",
        data: {
            bio: bio,
        },
        success: function (result) {
            if (result.success == true) {
                $(".bio-section").html(
                    '<p class="detail-bio">' + bio + "</p>"
                );
            }
        },
        error: function (err) {
            console.log("err ", err);
        },
    });
});

$("#OpenImgUpload").on("click", function () {
    $("#imgUpload").trigger("click");
});

$("#imgUpload").change(function () {
    readURL(this);
});

$("#toggleCurrentPassword").on("click", function () {
    $(this).toggleClass("fa-eye fa-eye-slash");
    // var input = $($(this).attr("toggle"));
    var input = $("#current_password");
    if (input.attr("type") == "password") {
        input.attr("type", "text");
    } else {
        input.attr("type", "password");
    }
});

$("#toggleNewPassword").on("click", function () {
    $(this).toggleClass("fa-eye fa-eye-slash");
    // var input = $($(this).attr("toggle"));
    var input = $("#new_password");
    if (input.attr("type") == "password") {
        input.attr("type", "text");
    } else {
        input.attr("type", "password");
    }
});

$("#toggleConfirmPassword").on("click", function () {
    $(this).toggleClass("fa-eye fa-eye-slash");
    // var input = $($(this).attr("toggle"));
    var input = $("#confirm_password");
    if (input.attr("type") == "password") {
        input.attr("type", "text");
    } else {
        input.attr("type", "password");
    }
});

$(".event-data").on("click", function () {
    $(".event-data").removeClass("active");
    $(this).addClass("active");
    var id = $(this).attr("id").split("-")[1];
    $.ajax({
        type: "GET",
        url: base_url + "/getOrder/" + id,
        success: function (result) {
            if (result.success == true) {
                if (result.data.event.type == "online") {
                    var type = "Online Event";
                } else {
                    var type = result.data.event.address;
                }
                if (result.data.order_status == "Pending") {
                    var status = "badge-warning";
                } else if (result.data.order_status == "Complete") {
                    var status = "badge-success";
                } else if (result.data.order_status == "Cancel") {
                    var status = "badge-danger";
                }
                if (result.data.payment_status == 1) {
                    var payment_status_class = "badge-success";
                    var payment_status = "Paid";
                }
                if (result.data.payment_status == 0) {
                    var payment_status_class = "badge-warning";
                    var payment_status = "Waiting";
                }
                if (
                    (result.data.review == null &&
                        result.data.order_status == "Complete") ||
                    result.data.order_status == "Cancel"
                ) {
                    var review_content =
                        '<div><button class="btn open-addReview"  data-toggle="modal" data-id="' +
                        result.data.id +
                        '" data-order="' +
                        result.data.order_id +
                        '"  data-target="#reviewModel"><i class="fa fa-star"></i></button><p>Review</p></div>';
                } else {
                    var review_content = "";
                }
                if (result.data.review != null) {
                    var review_content = "";
                }
                var rating =
                    result.data.review != null
                        ? '<div class="rating order-rate"></div>'
                        : "";

                var payment_token =
                    result.data.payment_token == null
                        ? "-"
                        : result.data.payment_token;

                $(".single-order").html(
                    '<div class="single-order-top"></div><div class="order-bottom"></div>'
                );
                $(".single-order-top").append(
                    '<p class="text-light mb-0">' +
                        result.data.order_id +
                        "</p><h4>Booked on :" +
                        " " +
                        result.data.time +
                        '</h4> <span class="badge ' +
                        status +
                        '">' +
                        result.data.order_status +
                        "</span>" +
                        rating +
                        '<div class="" id="mycustomqrcode"></div>' +
                        '<div class="row mt-2"><div class="col-lg-2"><img class="w-100" src="' +
                        base_url +
                        "/images/upload/" +
                        result.data.event.image +
                        '">\
                </div><div class="col-5"><h6 class="mb-0">' +
                        result.data.event.name +
                        '</h6><p class="mb-0">By: ' +
                        result.data.organization.first_name +
                        " " +
                        result.data.organization.last_name +
                        '</p><p class="mb-0">' +
                        result.data.start_time +
                        ' to </p><p class="mb-0">' +
                        result.data.end_time +
                        '</p><p class="mb-0">' +
                        type +
                        '</p></div><div class="col-5 "> <div class="right-data text-center"><div><button class="btn" onclick="viewPayment()"><i class="fa fa-credit-card"></i></button><p>Payment</p></div>' +
                        review_content +
                        '<div>\
                <a class="btn" target="_blank" href="' +
                        base_url +
                        "/order-invoice-print/" +
                        result.data.id +
                        '"><i class="fa fa-print"></i></a><p>Print</p></div><div><a href="show-details/' +
                        result.data.id +
                        '" target="_blank" class="btn" > <i class="fa fa-ticket"></i> <p>Show</p></a></div> </div><div class="payment-data hide" ><p class="mb-0"><span>Payment Method : </span>' +
                        result.data.payment_type +
                        '</p><p class="mb-1"><span>Payment Token : </span>' +
                        payment_token +
                        '</p><span class="badge ' +
                        payment_status_class +
                        '">' +
                        payment_status +
                        "</span>  </div>  </div></div>"
                );

                // var qrcode = new QRCode(document.getElementById("mycustomqrcode"), result.data.order_id);

                if (result.data.ticket.type == "free") {
                    $(".order-bottom").append(
                        '<div class="order-ticket-detail mb-4"><div><p>' +
                            result.data.ticket.name +
                            "</p></div><div> " +
                            result.data.quantity +
                            ' tickets</div></div><div class="order-total"> <p>Ticket Price</p><p> FREE</p></div><div class="order-total"> <p>Coupon discount</p><p> 0.00</p></div><div class="order-total"><p>Tax</p><p> 0.00</p></div><div class="order-total"> <h6>Total</h6><h6>FREE</h6></div>'
                    );
                } else {
                    $(".order-bottom").append(
                        '<div class="order-ticket-detail mb-4"><div><p>' +
                            result.data.ticket.name +
                            "</p></div><div> " +
                            result.data.quantity +
                            " X " +
                            cur +
                            result.data.ticket.price +
                            '</div></div> <div class="taxes"></div> <div class="order-total"><p>Tax</p><p>(+) ' +
                            cur +
                            result.data.tax +
                            '</p></div> <div class="order-total"> <p>Ticket Price</p><p> ' +
                            cur +
                            result.data.ticket.price *
                                result.data.quantity +
                            '</p></div><div class="order-total"> <p>Coupon discount</p><p>(-) ' +
                            cur +
                            result.data.coupon_discount +
                            '</p></div><div class="order-total"> <h6>Total</h6><h6>' +
                            cur +
                            result.data.payment +
                            "</h6></div>"
                    );
                }
                result.data.maintax.forEach((element) => {
                    if (element.amount_type == "price") {
                        $(".taxes").append(
                            '<div class="order-total"><p>' +
                                element.name +
                                "</p><p>" +
                                element.price +
                                "</p></div>"
                        );
                    }
                    if (element.amount_type == "percentage") {
                        $(".taxes").append(
                            '<div class="order-total"><p>' +
                                element.name +
                                "&nbsp; &nbsp;( " +
                                element.price +
                                "% )" +
                                "</p><p >" +
                                (result.data.ticket.price *
                                    result.data.quantity *
                                    element.price) /
                                    100 +
                                "</p></div>"
                        );
                    }
                });

                if (result.data.review != null) {
                    for (i = 1; i <= 5; i++) {
                        var active =
                            result.data.review.rate >= i ? "active" : "";
                        $(".single-order-top .order-rate").append(
                            '<i class="fa fa-star ' + active + ' mr-1"></i>'
                        );
                    }
                    $(".single-order-top .order-rate").append(
                        '<span class="ml-3 text-white">' +
                            result.data.review.message +
                            "</span>"
                    );
                }
            }
        },
        error: function (err) {
            console.log("err ", err);
        },
    });
});

$(".chip-button").on("click", function () {
    var type = $(this).attr("id").split("-")[0];
    var id = $(this).attr("id").split("-")[1];
    window.location.replace(base_url + "/all-events");
});

    $("#duration").change(function () {
        if (this.value == "date") {
            $(".date-section").removeClass("hidden");
            $(".date-section").addClass("visible");
        } else {
            $(".date-section").addClass("hidden");
        }
    });

    let preType = "";
    var seatMapStripe = null;
    var seatMapStripeElements = null;
    var seatMapStripeCard = null;

    function hasVenueSeatMapCheckout() {
        var selectedVenueSeatIds = $("#selectedVenueSeatIds").val();
        if (!selectedVenueSeatIds && $("#checkout-seat-hold-timer").length) {
            selectedVenueSeatIds = $("#checkout-seat-hold-timer").data("venue-seat-ids") || "";
        }

        return $.trim(selectedVenueSeatIds || "") !== "";
    }

    function goToOrderSuccess(orderId, fallbackUrl) {
        if (typeof window.loadOrderSuccessIntoModal === "function") {
            window.loadOrderSuccessIntoModal(orderId);
            return;
        }

        // Not inside the event-detail modal (e.g. the standalone checkout page) — fall back to a full navigation.
        window.location.replace(fallbackUrl);
    }

    function showStripeMessage(message) {
        $("#stripe_message").text(message).show();
        $(".stripe_alert").removeClass("hidden").show();
        $(".stripeText").text(message);
    }

    function hideStripeMessage() {
        $("#stripe_message").hide().text("");
        $(".stripe_alert").addClass("hidden").hide();
        $(".stripeText").text("");
    }

    function buildCheckoutRequestData(paymentType, paymentToken) {
        var ticketData = {};
        $(".ticketqtyseprate").each(function () {
            var id = $(this).attr("id");
            var quantity = $(this).val();
            ticketData[id] = quantity;
        });

        var requestData = {
            payment: $("#payment").val(),
            payment_token: paymentToken || null,
            payment_type: paymentType,
            ticket_id: $("#ticket_id").val(),
            coupon_code: $("#coupon_id").val(),
            tax: $("#tax_total").val(),
            quantity: $("#quantity").val(),
            ticket_date: $("#onetime").val(),
            selectedSeats: $("#selectedSeats").val(),
            selectedSeatsId: $("#selectedSeatsId").val(),
            event_id: $("#event_id").val() || $("input[name=event_id]").val(),
            ticketqty: JSON.stringify(ticketData),
            org_revenue: $("#org_revenue").val(),
            admin_revenue: $("#admin_revenue").val(),
        };

        if ($("#is_auth").val() === "0") {
            var phone = $("#phone").val();
            var countries = $("#countries").val();

            requestData.guest_first_name = $("#first_name").val();
            requestData.guest_last_name = $("#last_name").val();
            requestData.guest_phone = countries ? "+" + countries + phone : phone;
            requestData.guest_email = $("#email").val();
        }

        return requestData;
    }

    function initializeSeatMapStripeElements() {
        if (seatMapStripeCard) {
            return true;
        }

        if (typeof Stripe === "undefined" || !$("#stripePublicKey").val()) {
            showStripeMessage("Stripe is not available. Please refresh and try again.");
            return false;
        }

        if (!$("#card-number").length || !$("#card-expiry").length || !$("#card-cvc").length) {
            showStripeMessage("Stripe card form is not available for this checkout.");
            return false;
        }

        seatMapStripe = Stripe($("#stripePublicKey").val());
        seatMapStripeElements = seatMapStripe.elements();

        var elementStyle = {
            base: {
                fontSize: "16px",
                color: "#111827",
                "::placeholder": {
                    color: "#9ca3af",
                },
            },
            invalid: {
                color: "#dc2626",
            },
        };

        seatMapStripeCard = {
            number: seatMapStripeElements.create("cardNumber", { style: elementStyle }),
            expiry: seatMapStripeElements.create("cardExpiry", { style: elementStyle }),
            cvc: seatMapStripeElements.create("cardCvc", { style: elementStyle }),
        };

        seatMapStripeCard.number.mount("#card-number");
        seatMapStripeCard.expiry.mount("#card-expiry");
        seatMapStripeCard.cvc.mount("#card-cvc");

        Object.keys(seatMapStripeCard).forEach(function (key) {
            var elementId = key === "number" ? "#card-number" : key === "expiry" ? "#card-expiry" : "#card-cvc";
            var elementWrapper = $(elementId);

            seatMapStripeCard[key].on("focus", function () {
                elementWrapper.addClass("is-focused");
            });

            seatMapStripeCard[key].on("blur", function () {
                elementWrapper.removeClass("is-focused");
            });

            seatMapStripeCard[key].on("change", function (event) {
                if (event.error) {
                    elementWrapper.addClass("is-invalid");
                    showStripeMessage(event.error.message);
                } else {
                    elementWrapper.removeClass("is-invalid");
                    hideStripeMessage();
                }
            });
        });

        return true;
    }

    function useSeatMapStripeForm() {
        $("#paypal-button-container").hide();
        $("#form_submit").off("click.checkoutStripe").hide();
        $("#guestcheckoutbutton, .mobile-guestcheckoutbutton").hide();
        $("#stripeform").removeClass("hidden").show();
        if ($("#is_auth").val() === "0") {
            $('input[name="card_email"]').val($("#email").val());
            $('input[name="card_name"]').val($.trim($("#first_name").val() + " " + $("#last_name").val()));
        }
        hideStripeMessage();
        initializeSeatMapStripeElements();
        if ($("#stripeform").length) {
            $("#stripeform")[0].scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    function resetSeatMapStripeSubmitButton() {
        var submitButton = $("#stripe-payment-form").find('button[type="submit"]');
        var originalText = submitButton.data("original-text") || "Pay with stripe";
        submitButton.prop("disabled", false).text(originalText);
    }

    window.hasVenueSeatMapCheckout = hasVenueSeatMapCheckout;
    window.openSeatMapStripeForm = useSeatMapStripeForm;

    $(document).on(
        "change",
        ".payments input[type=radio][name=payment_type]",
        function () {
            var ticketDateInput = document.querySelector(
                'input[name="ticket_date"]'
            );
            var stripeRadio = document.querySelector(
                '.payments input[type=radio][name="payment_type"][value="STRIPE"]'
            );
            var paypalRadio = document.querySelector(
                '.payments input[type=radio][name="payment_type"][value="PAYPAL"]'
            );
            var razorRadio = document.querySelector(
                '.payments input[type=radio][name="payment_type"][value="RAZOR"]'
            );
            var flutterRadio = document.querySelector(
                '.payments input[type=radio][name="payment_type"][value="FLUTTERWAVE"]'
            );
            if (ticketDateInput && !ticketDateInput.value) {
                $(".ticket_date").text("The ticket date is required");
                $("#paypal-button-container").hide();
                $("#stripeform").hide();
                $("#form_submit").show();
                $(".payments input[type=radio][name=payment_type]").prop(
                    "checked",
                    false
                );
            } else if (stripeRadio && stripeRadio.checked) {
                $(".ticket_date").html("");
                $("#paypal-button-container").hide();
                if (hasVenueSeatMapCheckout()) {
                    useSeatMapStripeForm();
                } else {
                    $("#stripeform").addClass("hidden").hide();
                    $("#form_submit").show();
                    $("#form_submit").off("click.checkoutStripe").on("click.checkoutStripe", function (e) {
                        e.preventDefault();
                        stripeSession();
                    });
                }
            } else if (paypalRadio && paypalRadio.checked) {
                $(".ticket_date").html("");
                $("#stripeform").hide();
                $("#form_submit").attr("disabled", true);
                $("#paypal-button-container").show();
            } else if (razorRadio && razorRadio.checked) {
                $(".ticket_date").html("");
                $("#stripeform").hide();
                $("#form_submit").hide();
                $("#paypal-button-container").hide();
                var razorpayOptions = {
                    key: $("#razor_key").val(),
                    amount: $("#payment").val() * 100,
                    name: "CodesCompanion",
                    description: "test",
                    capture: true,
                    image: "https://i.imgur.com/n5tjHFD.png",
                    handler: demoSuccessHandler,
                };

                window.r = new Razorpay(razorpayOptions);
                r.open();
            } else if (flutterRadio && flutterRadio.checked) {
                FlutterwaveCheckout({
                    public_key: $("input[name=flutterwave_key]").val(),
                    tx_ref:
                        Math.floor(Math.random() * (1000 - 9999 + 1)) + 9999,
                    amount: $("#payment").val(),
                    currency: $("input[name=currency_code]").val(),
                    payment_options: " ",
                    customer: {
                        email: $("input[name=email]").val(),
                        phone_number: $("input[name=phone]").val(),
                        name: $("input[name=name]").val(),
                    },
                    callback: function (data) {
                        if (data.status == "successful") {
                            $("#payment_token").val(data.transaction_id);
                            $("#form_submit").attr("disabled", false);
                            $("#form_submit").trigger("click");

                            $("#form_submit").attr("disabled", false);
                            $("input[name=payment_status]").val(1);
                            $("input[name=payment_token]").val(
                                data.transaction_id
                            );
                            $("input[name=payment_type]").val("FLUTTERWAVE");
                            document.getElementById("ticketorder").submit();
                            booking();
                        }
                    },
                    customizations: {
                        title: $("input[name=company_name]").val(),
                        description: "Doctor Appointment Booking",
                    },
                });
            }

            $("#paypal-button-container").html("");
            $(".paypal-button-section").hide();
            $(".stripe-form-section").hide();
            $(".stripe-form").html("");
            if (this.value == "PAYPAL") {
                $("#form_submit").attr("disabled", true);
                paypal_sdk
                    .Buttons({
                        createOrder: function (data, actions) {
                            return actions.order.create({
                                purchase_units: [
                                    {
                                        amount: {
                                            value: $("#payment").val(),
                                        },
                                    },
                                ],
                            });
                        },
                        onApprove: function (data, actions) {
                            return actions.order
                                .capture()
                                .then(function (details) {
                                    $("#payment_token").val(details.id);
                                    $("#form_submit").attr("disabled", false);

                                    var requestData = {
                                        payment: $("#payment").val(),
                                        payment_token: details.id,
                                        payment_type: "PAYPAL",
                                        ticket_id: $("#ticket_id").val(),
                                        coupon_code: $("#coupon_id").val(),
                                        tax: $("#tax_total").val(),
                                        quantity: $("#quantity").val(),
                                        ticket_date: $("#onetime").val(),
                                        selectedSeats:
                                            $("#selectedSeats").val(),
                                        selectedSeatsId:
                                            $("#selectedSeatsId").val(),
                                    };

                                    $.ajax({
                                        headers: {
                                            "X-CSRF-TOKEN": $(
                                                'meta[name="csrf-token"]'
                                            ).attr("content"),
                                        },
                                        url: "/createOrder",
                                        type: "POST",
                                        data: requestData,

                                        success: function (data) {
                                            if (data.success == true) {
                                                goToOrderSuccess(data.id, "/" + (data.url || "ordersuccessuser") + "?id=" + data.id);
                                            } else {
                                                $("#stripe_message").text(
                                                    data.message
                                                );
                                                $("#stripe_message").show();
                                            }
                                        },
                                        error: function (data) {
                                            if (data.status === 422) {
                                                var errors =
                                                    data.responseJSON.errors;
                                                console.log(
                                                    errors.ticket_date[0]
                                                );
                                                $(".ticket_date").text(
                                                    errors.ticket_date[0]
                                                );
                                            }
                                        },
                                    });
                                });
                        },
                    })
                    .render("#paypal-button-container");

                $(".paypal-button-section").show(500);
            }
            if (this.value == "LOCAL") {
                $("#form_submit").show();
                $("#paypal-button-container").hide();
                $("#stripeform").hide();
                $("#form_submit").on("click", function () {
                    var requestData = {
                        payment: $("#payment").val(),
                        payment_token: null,
                        payment_type: "LOCAL",
                        ticket_id: $("#ticket_id").val(),
                        coupon_code: $("#coupon_id").val(),
                        tax: $("#tax_total").val(),
                        quantity: $("#quantity").val(),
                        ticket_date: $("#onetime").val(),
                        selectedSeats: $("#selectedSeats").val(),
                        selectedSeatsId: $("#selectedSeatsId").val(),
                    };
                    createOrder(requestData);
                });
            }
            if (this.value == "FREE") {
                $("#form_submit").show();
                $("#paypal-button-container").hide();
                $("#stripeform").hide();
                $("#form_submit").off("click.checkoutFree").on("click.checkoutFree", function () {
                    var ticketData = {};
                    $('.ticketqtyseprate').each(function() {
                        var id = $(this).attr('id');
                        var quantity = $(this).val();
                        ticketData[id]=quantity;
                    });
                var ticketqty= JSON.stringify(ticketData);
                var org_revenue= $("#org_revenue").val();
                var admin_revenue=$("#admin_revenue").val();
                    var requestData = {
                        payment: 0,
                        payment_token: null,
                        payment_type: "FREE",
                        ticket_id: $("#ticket_id").val(),
                        coupon_code: $("#coupon_id").val(),
                        tax: $("#tax_total").val(),
                        quantity: $("#quantity").val(),
                        ticket_date: $("#onetime").val(),
                        selectedSeats: $("#selectedSeats").val(),
                        selectedSeatsId: $("#selectedSeatsId").val(),
                        event_id: $("input[name=event_id]").val(),
                        ticketqty:ticketqty,
                        org_revenue:org_revenue,
                        admin_revenue:admin_revenue,
                    };
                    createOrder(requestData);
                });
            }
            preType = this.value;
            if (this.value == "wallet") {
                $("#form_submit").show();
                $("#paypal-button-container").hide();
                $("#stripeform").hide();
                $("#form_submit").on("click", function () {

                    var requestData = {
                        payment: $("#payment").val(),
                        payment_token: null,
                        payment_type: "WALLET",
                        ticket_id: $("#ticket_id").val(),
                        coupon_code: $("#coupon_id").val(),
                        tax: $("#tax_total").val(),
                        quantity: $("#quantity").val(),
                        ticket_date: $("#onetime").val(),
                        selectedSeats: $("#selectedSeats").val(),
                        selectedSeatsId: $("#selectedSeatsId").val(),
                    };
                    createOrder(requestData);
                });
            }
        }
    );
    $(document).on("submit", "#stripe-payment-form", function (e) {
        if (!hasVenueSeatMapCheckout()) {
            return;
        }

        e.preventDefault();

        if (!initializeSeatMapStripeElements()) {
            return;
        }

        if ($("#is_auth").val() === "0") {
            if (!validateGuestRequiredFields() || !requireGuestEmailVerification()) {
                resetSeatMapStripeSubmitButton();
                return;
            }
        }

        var submitButton = $(this).find('button[type="submit"]');
        var originalText = submitButton.text();
        var requestData = buildCheckoutRequestData("STRIPE", null);
        var cardholderName = $.trim($('input[name="card_name"]').val() || "");
        var cardholderEmail = $.trim($('input[name="card_email"]').val() || $('input[name="email"]').val() || "");

        hideStripeMessage();
        submitButton.data("original-text", originalText);
        submitButton.prop("disabled", true).text("Processing...");

        $.ajax({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            url: "/user/stripe/create-seat-map-payment-intent",
            type: "POST",
            data: appendVenueSeatPayload(requestData),
            dataType: "json",
            success: function (response) {
                if (!response.success || !response.client_secret) {
                    showStripeMessage(response.message || "Unable to start Stripe payment.");
                    submitButton.prop("disabled", false).text(originalText);
                    return;
                }

                seatMapStripe.confirmCardPayment(response.client_secret, {
                    payment_method: {
                        card: seatMapStripeCard.number,
                        billing_details: {
                            name: cardholderName,
                            email: cardholderEmail,
                        },
                    },
                }).then(function (result) {
                    if (result.error) {
                        showStripeMessage(result.error.message);
                        submitButton.prop("disabled", false).text(originalText);
                        return;
                    }

                    if (!result.paymentIntent || result.paymentIntent.status !== "succeeded") {
                        showStripeMessage("Stripe payment was not completed.");
                        submitButton.prop("disabled", false).text(originalText);
                        return;
                    }

                    $("#payment_token").val(result.paymentIntent.id);
                    requestData.payment_token = result.paymentIntent.id;
                    createOrder(requestData, { skipFormToggle: true });
                });
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};

                if (response.expired && response.redirect_url) {
                    window.location.replace(response.redirect_url);
                    return;
                }

                showStripeMessage(response.message || "Unable to start Stripe payment.");
                submitButton.prop("disabled", false).text(originalText);
            },
        });
    });
    // Create Order
    function createOrder(requestData, options) {
        requestData = appendVenueSeatPayload(requestData);
        var skipFormToggle = options && options.skipFormToggle;

        $.ajax({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            url: "/createOrder",
            type: "POST",
            data: requestData,
            beforeSend: function () {
                if (skipFormToggle) return;
                $("#formtext").hide(100);
                $("#formloader").show(100);
            },
            complete: function () {
                if (skipFormToggle) return;
                $("#formloader").hide(100);
                $("#formtext").show(100);
            },
            success: function (data) {
                if (data.success == true) {
                    goToOrderSuccess(data.id, "/" + (data.url || "ordersuccessuser") + "?id=" + data.id);
                } else {
                    $("#stripe_message").text(data.message);
                    $("#stripe_message").show();
                    resetSeatMapStripeSubmitButton();
                }
            },
            error: function (data) {
                if (data.status === 422) {
                    var errors = data.responseJSON.errors;
                    if (errors && errors.ticket_date) {
                        console.log(errors.ticket_date[0]);
                        $(".ticket_date").text(errors.ticket_date[0]);
                    } else if (data.responseJSON && data.responseJSON.message) {
                        showStripeMessage(data.responseJSON.message);
                    }
                    resetSeatMapStripeSubmitButton();
                }
            },
        });
    }

    $("#imageUploadForm").on("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            type: "POST",
            url: base_url + "/upload-profile-image",
            data: formData,
            contentType: false,
            processData: false,
            success: function (result) {
                if (result) {
                    $("#profileDropdown .header-profile-img").attr(
                        "src",
                        base_url + "/images/upload/" + result.data
                    );
                    window.location.reload();
                }
            },
            error: function (err) {
                console.log(err);
            },
        });
    });

    $(document).on("change", "#onetime", function () {
        var lastDate = new Date($("#onetime").data("date"));
        var selectedDate = new Date($(this).val());
        if (selectedDate > lastDate) {
            var formattedLastDate = formatDate(lastDate);
            $(this).val(formattedLastDate);
        }
    });

    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, "0");
        var day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }
});

function addFavorite(id, type) {
    $.ajax({
        type: "GET",
        url: base_url + "/add-favorite/" + id + "/" + type,
        success: function (result) {
            if (result.success == true) {
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            }
        },
        error: function (err) {
            console.log("err ", err);
        },
    });
}

function demoSuccessHandler(transaction) {
    $("#payment_token").val(transaction.razorpay_payment_id);
    $("#form_submit").attr("disabled", false);
    var requestData = {
        payment: $("#payment").val(),
        payment_token: transaction.razorpay_payment_id,
        payment_type: "RAZORPAY",
        ticket_id: $("#ticket_id").val(),
        coupon_code: $("#coupon_id").val(),
        tax: $("#tax_total").val(),
        quantity: $("#quantity").val(),
        ticket_date: $("#onetime").val(),
        selectedSeats: $("#selectedSeats").val(),
        selectedSeatsId: $("#selectedSeatsId").val(),
    };

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        url: "/createOrder",
        type: "POST",
        data: requestData,

        success: function (data) {
            if (data.success == true) {
                $("#stripe_message").text(data.message);
                window.location.href = "/my-tickets";
            } else {
                $("#stripe_message").text(data.message);
                $("#stripe_message").show();
            }
        },
        error: function (data) {
            if (data.status === 422) {
                var errors = data.responseJSON.errors;
                console.log(errors.ticket_date[0]);
                $(".ticket_date").text(errors.ticket_date[0]);
            }
        },
    });
}

function viewPayment() {
    $(".payment-data").slideToggle();
}

function addRate(id) {
    $(".rating i").css("color", "#d2d2d2");
    $('#reviewModel input[name="rate"]').val(id);
    for (let i = 1; i <= id; i++) {
        $(".rating #rate-" + i).css("color", "#fec009");
    }
    $("#rate").val(id);
}

function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#imagePreview").attr("src", e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
    $("#imageUploadForm").submit();
}

function follow(id) {
    $.ajax({
        type: "GET",
        url: base_url + "/add-followList/" + id,
        success: function (result) {
            if (result.success == true) {
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            }
        },
        error: function (err) {
            console.log("err ", err);
        },
    });
}
$("#onetime").flatpickr({
    minDate: "today",
    dateFormat: "Y-m-d",
});
$("#start_time,#end_time").flatpickr({
    minDate: "today",
    dateFormat: "Y-m-d",
});
function imagegallery(params) {
    var origin = window.location.origin;
    $("#eventimage").attr("src", origin + "/images/upload/" + params);
}

function appendVenueSeatPayload(requestData) {
    var selectedVenueSeatIds = $("#selectedVenueSeatIds").val();
    if (!selectedVenueSeatIds && $("#checkout-seat-hold-timer").length) {
        selectedVenueSeatIds = $("#checkout-seat-hold-timer").data("venue-seat-ids") || "";
    }

    requestData.selectedVenueSeatIds = selectedVenueSeatIds;
    requestData.selectedVenueSeats = $("#selectedVenueSeats").val();

    return requestData;
}

function stripeSession() {
    var is_auth = $("#is_auth").val();
    if (is_auth === '0' && isMobileGuestCheckoutView() && isMobileGuestCheckoutProcessing()) {
        return;
    }

    if (is_auth === '0' && !requireGuestEmailVerification()) {
        return;
    }

    var ticketData = {};
    $('.ticketqtyseprate').each(function() {
        var id = $(this).attr('id');
        var quantity = $(this).val();
        ticketData[id]=quantity;
    });
   var ticketqty= JSON.stringify(ticketData);
   var org_revenue= $("#org_revenue").val();
   var admin_revenue=$("#admin_revenue").val();
   // Dynamic values: Replace or update these as per your setup
   var totalValue = $("#payment").val() || '0.00'; // Replace with dynamic total logic
   var eventName = $('#eventName').val() || 'Unnamed Event'; // Replace with dynamic event name logic
   var metaPixelId = $('#metaPixelId').val() || 0; // Replace with dynamic event name logic

   // Send the Meta Pixel event
   !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', metaPixelId);
    fbq('track', 'InitiateCheckout', {
       content_name: eventName, // The name of the event being purchased
       content_category: 'Event Tickets', // Relevant category for the event
       value: totalValue, // Total checkout value
       currency: 'USD', // Replace with your currency (e.g., INR, GBP, etc.)
    });
    if(is_auth === '0'){
        if($("#first_name").val()==''){
            $("#first_name_span").html('First Name required');
            $(".payments input[type=radio][name=payment_type]").prop(
                "checked",
                false
            );
            return;
        }
        if($("#last_name").val()==''){
            $("#last_name_span").html('Last Name required');
            $(".payments input[type=radio][name=payment_type]").prop(
                "checked",
                false
            );
            return;
        }
        if($("#countries").val()==null){
            $("#countries_span").html('Country required');
            $(".payments input[type=radio][name=payment_type]").prop(
                "checked",
                false
            );
            return;
        }
        if($("#phone").val()==''){
            $("#phone_span").html('Contact Number required');
            $(".payments input[type=radio][name=payment_type]").prop(
                "checked",
                false
            );
            return;
        }
        if($("#email").val()==''){
            $("#email_span").html('Email required');
            $(".payments input[type=radio][name=payment_type]").prop(
                "checked",
                false
            );
            return;
        }

        var phone = $("#phone").val();
        var countries = $("#countries").val();
        phone="+"+countries+phone;
        var postData= {
            payment: $("#payment").val(),
            payment_type: "STRIPE",
            ticket_id: $("#ticket_id").val(),
            coupon_code: $("#coupon_id").val(),
            tax: $("#tax_total").val(),
            quantity: $("#quantity").val(),
            ticket_date: $("#onetime").val(),
            selectedSeats: $("#selectedSeats").val(),
            selectedSeatsId: $("#selectedSeatsId").val(),
            guest_first_name:$("#first_name").val(),
            guest_last_name:$("#last_name").val(),
            guest_phone:phone,
            guest_email:$("#email").val(),
            event_id: $("input[name=event_id]").val(),
            ticketqty:ticketqty,
            org_revenue:org_revenue,
            admin_revenue:admin_revenue,
        };
        setMobileGuestCheckoutProcessing(true);
    }else{
        var postData= {
            payment: $("#payment").val(),
            payment_type: "STRIPE",
            ticket_id: $("#ticket_id").val(),
            coupon_code: $("#coupon_id").val(),
            tax: $("#tax_total").val(),
            quantity: $("#quantity").val(),
            ticket_date: $("#onetime").val(),
            selectedSeats: $("#selectedSeats").val(),
            selectedSeatsId: $("#selectedSeatsId").val(),
            event_id: $("input[name=event_id]").val(),
            ticketqty:ticketqty,
            org_revenue:org_revenue,
            admin_revenue:admin_revenue,
        };
    }
    var stripe = Stripe($("#stripePublicKey").val());
    var origin = window.location.origin;
    var url = origin + "/user/stripe/create-session";
    postData = appendVenueSeatPayload(postData);
    $.ajax({
        url: url,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: postData,
        dataType: "json",
        success: function (session) {
            // Stripe's hosted checkout page refuses to render inside an iframe (it sets its own
            // frame-blocking headers), so when this runs inside the in-page ticket flow modal,
            // break out to the top-level window instead of letting Stripe.js redirect the iframe.
            if (window.self !== window.top && session.url) {
                window.top.location.href = session.url;
                return;
            }
            stripe
                .redirectToCheckout({
                    sessionId: session.id,
                })
                .then(function (result) {
                    console.log(result);
                    if (result.error) {
                        alert(result.error.message);
                        if (is_auth === '0') {
                            setMobileGuestCheckoutProcessing(false);
                        }
                    }
                });
        },
        error: function (error) {
            console.error("Error:", error);
            if (is_auth === '0') {
                setMobileGuestCheckoutProcessing(false);
            }
        },
    });
}

// Wallet
$(document).ready(function () {
    // PayPal
    $("#paypalWallet").on("click", function () {
        paypal_sdk
            .Buttons({
                createOrder: function (data, actions) {
                    return actions.order.create({
                        purchase_units: [
                            {
                                amount: {
                                    value: $("#amount").val(),
                                },
                            },
                        ],
                    });
                },
                onApprove: function (data, actions) {
                    return actions.order.capture().then(function (details) {
                        var token = details.id;
                        var amount = $("#amount").val();
                        var payment_type = "PAYPAL";
                        addMoneyWallet(token, amount, payment_type);
                    });
                },
            })
            .render("#paypal-button-container");

        $(".paypal-button-section").show(500);
    });
    // Razorpay
    $("#razorpayWallet").on("click", function () {
        var razorpayOptions = {
            key: $("#razor_key").val(),
            amount: $("#amount").val() * 100,
            name: "Add to Wallet",
            description: "",
            capture: true,
            image: "https://i.imgur.com/n5tjHFD.png",
            handler: demoSuccessHandlerWallet,
        };
        window.r = new Razorpay(razorpayOptions);
        r.open();
    });
    function demoSuccessHandlerWallet(transaction) {
        var amount = $("#amount").val();
        var token = transaction.razorpay_payment_id;
        var payment_type = "RAZORPAY";
        addMoneyWallet(token, amount, payment_type);
    }
    // Stripe
    $("#stripeWallet").on("click", function () {
        var stripe = Stripe($("#stripePublicKey").val());
        var url = "/user/wallet/stripe/create-session";
        var currency = $("#walletCur").val();
        console.log($("#amount").val());
        $.ajax({
            url: url,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: {
                amount: $("#amount").val(),
                payment_type: "STRIPE",
                currency: currency,
            },
            dataType: "json",
            success: function (session) {
                stripe
                    .redirectToCheckout({
                        sessionId: session.id,
                    })
                    .then(function (result) {
                        console.log(result);
                        if (result.error) {
                            alert(result.error.message);
                        }
                    });
            },
            error: function (error) {
                console.error("Error:", error);
            },
        });
    });
    // Flutterwave
    $("#flutterwaveWallet").on("click", function () {
        FlutterwaveCheckout({
            public_key: $("#flutterwave_key").val(),
            tx_ref: Math.floor(Math.random() * (1000 - 9999 + 1)) + 9999,
            amount: $("#amount").val(),
            currency: $("#walletCur").val(),
            payment_options: "card, banktransfer, ussd",
            customer: {
                email: $("input[name=email]").val(),
            },
            callback: function (data) {
                if (data.status == "successful") {
                    booking();
                    $("#payment_token").val(data.transaction_id);
                    var token = data.transaction_id;
                    var amount = $("#amount").val();
                    var payment_type = "FLUTTERWAVE";
                    addMoneyWallet(token, amount, payment_type);
                }
            },
            customizations: {
                title: "Add to Wallet",
                description: "Payment for adding funds to wallet",
            },
        });
    });
    function addMoneyWallet(token, amount, payment_type) {
        var currency = $("#walletCur").val();
        $.ajax({
            type: "post",
            url: "/user/deposite",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: {
                token: token,
                amount: amount,
                payment_type: payment_type,
                currency: currency,
            },
            dataType: "JSON",
            beforeSend: function () {},
            complete: function () {},
            success: function (response) {
                if (response.success == true) {
                    var newPath = "/user/wallet";
                    window.location.href = window.location.origin + newPath;
                }
            },
            error: function (response) {},
        });
    }

    $("#paymentbtn").on("click", function () {
        var min = 5;
        var input = $("#amount").val(); // Get the value directly

        if (input < min) {
            alert("Please enter a value greater than or equal to " + min);
        } else {
            $("#amount")[0].setCustomValidity("");
            $("#amount").prop("disabled", true);
            $(".payments").show(200);
        }
    });
});

// Carasoule
$(document).ready(function () {
    $('.your-carousel').slick({
        infinite: true, // Enable infinite loop
        speed: 300, // Transition speed
        slidesToShow: 1, // Number of slides to show at a time
        slidesToScroll: 1, // Number of slides to scroll at a time
        autoplay: true, // Auto play option
        autoplaySpeed: 5000 ,// Auto play speed in milliseconds
        dots: false, // Enable dots navigation
        arrows: false, // Enable arrows navigation
      });

      // Custom navigation button functionality
      $('.hs-carousel-prev').click(function() {
        $('.your-carousel').slick('slickPrev');
      });

      $('.hs-carousel-next').click(function() {
        $('.your-carousel').slick('slickNext');
      });
});

$(document).on('keyup', '#first_name', function(){
    $("#first_name_span").html('');
});
$(document).on('keyup', '#last_name', function(){
    $("#last_name_span").html('');
});
$(document).on('keyup', '#phone', function(){
    $("#phone_span").html('');
});
$(document).on('keyup', '#email', function(){
    $("#email_span").html('');
});
$(document).on('change', '#countries', function(){
    $("#countries_span").html('');
});

function validateGuestRequiredFields() {
    var isValid = true;

    $("#first_name_span, #last_name_span, #countries_span, #phone_span, #email_span").html('');

    if ($.trim($("#first_name").val()) === '') {
        $("#first_name_span").html('First Name required');
        isValid = false;
    }

    if ($.trim($("#last_name").val()) === '') {
        $("#last_name_span").html('Last Name required');
        isValid = false;
    }

    if ($("#countries").val() == null || $("#countries").val() === '') {
        $("#countries_span").html('Country required');
        isValid = false;
    }

    if ($.trim($("#phone").val()) === '') {
        $("#phone_span").html('Contact Number required');
        isValid = false;
    }

    if ($.trim($("#email").val()) === '') {
        $("#email_span").html('Email required');
        isValid = false;
    }

    return isValid;
}

function verifyemail(){
    if (!validateGuestRequiredFields()) {
        return;
    }

    var email = $("#email").val();
    var phone = $("#phone").val();
    var countries = $("#countries").val();
    phone="+"+countries+phone;
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if(!regex.test(email)){
        $("#email_span").html('Please enter valid email!');
        return;
    }
    var modalotp = document.getElementById("myModalotp");
    // var submitBtn = document.getElementById('guestcheckoutbutton');
    var origin = window.location.origin;
    var url = origin + "/user/verify-guestemail";
    $.ajax({
        url: url,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            email:email,
            phone:phone
        },
        dataType: "json",
        beforeSend: function () {
            $("#verifyemailtext").hide(100);
            $("#verifyemailloader").show(100);
        },
        complete: function () {
            $("#verifyemailloader").hide(100);
            $("#verifyemailtext").show(100);
        },
        success: function (data) {
            // Make Verify Email button grey/disabled after clicking
            $("#guestemailverifybutton")
                .prop("disabled", true)
                .removeClass("btn-primary")
                .addClass("btn-secondary");

            if (typeof openGuestOtpModal === 'function') {
                openGuestOtpModal();
            } else {
                $("#otpCard").removeClass("hidden");
                document.getElementById('otpCard').scrollIntoView({ behavior: 'smooth' });
            }
        },
        error: function (error) {
           var err= JSON.parse(error.responseText).errors
            if(err["email"]){
                $("#email_span").html(err.email[0]);
            }
            if(err["phone"]){
                $("#phone_span").html(err.phone[0]);
            }
        },
    });
}

function resendOtp(){
    var email = $("#email").val();
    var origin = window.location.origin;
    var url = origin + "/user/resend-otp";
    $.ajax({
        url: url,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            email:email
        },
        dataType: "json",
        success: function (data) {
            $("#otp_verify_span")
                .removeClass('text-danger')
                .addClass('text-success otp-result-message')
                .html('OTP has been sent.');

        },
        error: function (error) {

        },
    });
}

function getOTP() {
    var otp = '';
    for (let i = 1; i <= 4; i++) {
        const input = $("#otpinput"+i).val();//document.getElementById(`otpinput${i}`);
        console.log(input);
        otp += input;
    }
    return otp;
}

function verifyotp(){
    var modalotp = document.getElementById("myModalotp");
    const otp = getOTP();
    var email = $("#email").val();
    var origin = window.location.origin;
    var url = origin + "/user/verify-otp";
    $.ajax({
        url: url,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            otp:otp,
            email:email
        },
        dataType: "json",
        success: function (data) {
            $('#email').prop('readonly', true);
            window.guestEmailVerified = true;
            if (typeof updateGuestCheckoutState === 'function') {
                updateGuestCheckoutState(true);
            }
            $("#otp_verify_span")
                .removeClass('text-danger')
                .addClass('text-success otp-result-message')
                .html('Email verified successfully.');
            $('#guestcheckoutbutton').prop('disabled', false).removeClass('btn-disabled').show();
            $("#email_span").removeClass('text-danger');
            $("#email_span").addClass('text-success');
            $("#email_span").html('Email verified successfully.');
            $("#guestemailverifybutton").hide();

            var shouldAutoCheckout = (typeof isMobileCheckoutView === 'function' && isMobileCheckoutView());
            if (typeof closeGuestOtpModal === 'function') {
                closeGuestOtpModal(true);
            } else {
                var otpCard = document.getElementById('otpCard');
                if (otpCard) {
                    otpCard.classList.add('hidden');
                }
                if (modalotp) {
                    modalotp.style.display = "none";
                }
                document.body.classList.remove("modal-openotp");
            }

            if (shouldAutoCheckout) {
                if (typeof closeGuestCheckoutModal === 'function') {
                    closeGuestCheckoutModal();
                }

                var mobileActions = document.querySelector('.checkout-mobile-auth-actions');
                if (mobileActions) {
                    mobileActions.innerHTML = ''
                        + '<button type="button" onclick="guestcheckout()" '
                        + 'class="checkout-primary-btn checkout-guest-continue-btn mobile-guestcheckoutbutton w-full">'
                        + '<i class="fas fa-lock"></i>'
                        + '<span>Checkout</span>'
                        + '</button>';
                }
            }
        },
        error: function (error) {
            var err= JSON.parse(error.responseText).message
            $("#otp_verify_span").removeClass('text-success')
                .addClass('text-danger otp-result-message')
                .html(err);
        },
    });

}

function getSelectedGuestPaymentType() {
    var checkedPayment = $('.payments input[type=radio][name=payment_type]:checked').val();
    if (checkedPayment) {
        return checkedPayment;
    }

    var firstPayment = $('.payments input[type=radio][name=payment_type]').first();
    if (firstPayment.length) {
        firstPayment.prop('checked', true);
        return firstPayment.val();
    }

    return null;
}

function requireGuestEmailVerification() {
    if (window.guestEmailVerified === true) {
        return true;
    }

    $("#otp_verify_span")
        .removeClass('text-success text-danger otp-result-message')
        .html('');

    if (typeof updateGuestCheckoutState === 'function') {
        updateGuestCheckoutState(false);
    }

    return false;
}

function isMobileGuestCheckoutView() {
    return window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
}

function isMobileGuestCheckoutProcessing() {
    return $("#guestcheckoutbutton, .mobile-guestcheckoutbutton").filter(function () {
        return $(this).data("processing") === "1";
    }).length > 0;
}

function setMobileGuestCheckoutProcessing(isProcessing) {
    if (!isMobileGuestCheckoutView()) {
        return;
    }

    var button = $("#guestcheckoutbutton, .mobile-guestcheckoutbutton");
    if (!button.length) {
        return;
    }

    if (isProcessing) {
        button
            .data("processing", "1")
            .prop("disabled", true)
            .addClass("mobile-checkout-processing btn-disabled")
            .text("Processing Order...");
        return;
    }

    button
        .removeData("processing")
        .removeClass("mobile-checkout-processing")
        .prop("disabled", window.guestEmailVerified !== true)
        .toggleClass("btn-disabled", window.guestEmailVerified !== true)
        .text("Checkout");
}

function guestcheckout(){
    if (isMobileGuestCheckoutView() && isMobileGuestCheckoutProcessing()) {
        return;
    }

    if (!requireGuestEmailVerification()) {
        return;
    }

    var ticketData = {};
    $('.ticketqtyseprate').each(function() {
        var id = $(this).attr('id');
        var quantity = $(this).val();
        ticketData[id]=quantity;
    });
   var ticketqty= JSON.stringify(ticketData);
   var org_revenue= $("#org_revenue").val();
   var admin_revenue=$("#admin_revenue").val();
    var paytype = getSelectedGuestPaymentType();
    if (!paytype) {
        $("#stripe_message").text("Please select a payment method.");
        $("#stripe_message").show();
        return;
    }

    if(paytype == 'FREE'){

         if($("#first_name").val()==''){
             $("#first_name_span").html('First Name required');
             $(".payments input[type=radio][name=payment_type]").prop(
                 "checked",
                 false
             );
             return;
         }
         if($("#last_name").val()==''){
             $("#last_name_span").html('Last Name required');
             $(".payments input[type=radio][name=payment_type]").prop(
                 "checked",
                 false
             );
             return;
         }
         if($("#countries").val()==null){
             $("#countries_span").html('Country required');
             $(".payments input[type=radio][name=payment_type]").prop(
                 "checked",
                 false
             );
             return;
         }
         if($("#phone").val()==''){
             $("#phone_span").html('Contact Number required');
             $(".payments input[type=radio][name=payment_type]").prop(
                 "checked",
                 false
             );
             return;
         }
         if($("#email").val()==''){
             $("#email_span").html('Email required');
             $(".payments input[type=radio][name=payment_type]").prop(
                 "checked",
                 false
             );
             return;
         }
         setMobileGuestCheckoutProcessing(true);
         var phone = $("#phone").val();
         var countries = $("#countries").val();
         phone="+"+countries+phone;
         var requestData = {
             payment: 0,
             payment_token: null,
             payment_type: "FREE",
             ticket_id: $("#ticket_id").val(),
             coupon_code: $("#coupon_id").val(),
             tax: $("#tax_total").val(),
             quantity: $("#quantity").val(),
             ticket_date: $("#onetime").val(),
             selectedSeats: $("#selectedSeats").val(),
             selectedSeatsId: $("#selectedSeatsId").val(),
             guest_first_name:$("#first_name").val(),
             guest_last_name:$("#last_name").val(),
             guest_phone:phone,
             guest_email:$("#email").val(),
             event_id: $("input[name=event_id]").val(),
             ticketqty:ticketqty,
             org_revenue:org_revenue,
             admin_revenue:admin_revenue,
         };
         requestData = appendVenueSeatPayload(requestData);

         $.ajax({
             headers: {
                 "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
             },
             url: "/createOrder",
             type: "POST",
             data: requestData,
             beforeSend: function () {
                 $("#formtext").hide(100);
                 $("#formloader").show(100);
                 $("#guestcheckoutbutton").prop("disabled", true).text("Processing Order...");
             },
             complete: function () {
                 $("#formloader").hide(100);
                 $("#formtext").show(100);
                 if (isMobileGuestCheckoutProcessing()) {
                     return;
                 }
                 $("#guestcheckoutbutton").prop("disabled", false).text("Checkout");
             },
             success: function (data) {
                 if (data.success == true) {
                    goToOrderSuccess(data.id, data.url != 0 ? '/' + data.url : '/my-tickets');
                 } else {
                     $("#stripe_message").text(data.message);
                     $("#stripe_message").show();
                     setMobileGuestCheckoutProcessing(false);
                 }
             },
             error: function (data) {
                 setMobileGuestCheckoutProcessing(false);
                 if (data.status === 422) {
                     var response = data.responseJSON || {};
                     if (response.message) {
                         $("#stripe_message").text(response.message).show();
                         return;
                     }
                     var errors = response.errors || {};
                     if (errors.ticket_date && errors.ticket_date[0]) {
                         $(".ticket_date").text(errors.ticket_date[0]);
                     }
                 }
             },
         });
    }else if(paytype == 'STRIPE'){
         if (typeof window.hasVenueSeatMapCheckout === "function" && window.hasVenueSeatMapCheckout()) {
             if (!validateGuestRequiredFields()) {
                 return;
             }

             if (typeof window.openSeatMapStripeForm === "function") {
                 window.openSeatMapStripeForm();
             }
             return;
         }

         stripeSession();
    }else{
         $("#stripe_message").text("Unsupported payment method.");
         $("#stripe_message").show();
    }
}


