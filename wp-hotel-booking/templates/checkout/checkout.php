<?php
/**
 * The template for displaying checkout page.
 *
 * This template can be overridden by copying it to yourtheme/wp-hotel-booking/checkout/checkout.php.
 *
 * @author  ThimPress, leehld
 * @package WP-Hotel-Booking/Templates
 * @version 1.9.7.5
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * @var $cart        WPHB_Cart
 * @var $hb_settings WPHB_Settings
 * @var $customer
 */
global $hb_settings;
$cart = WP_Hotel_Booking::instance()->cart; ?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/stdpay/libs/INIStdPayUtil.php';
$SignatureUtil = new INIStdPayUtil();
/*
  //*** 위변조 방지체크를 signature 생성 ***

  oid, price, timestamp 3개의 키와 값을

  key=value 형식으로 하여 '&'로 연결한 하여 SHA-256 Hash로 생성 된값

  ex) oid=INIpayTest_1432813606995&price=819000&timestamp=2012-02-01 09:19:04.004


 * key기준 알파벳 정렬

 * timestamp는 반드시 signature생성에 사용한 timestamp 값을 timestamp input에 그대로 사용하여야함
 */

//############################################
// 1.전문 필드 값 설정(***가맹점 개발수정***)
//############################################
// 여기에 설정된 값은 Form 필드에 동일한 값으로 설정
$mid = "INIpayTest";  // 가맹점 ID(가맹점 수정후 고정)					
//인증
$signKey = "SU5JTElURV9UUklQTEVERVNfS0VZU1RS"; // 가맹점에 제공된 웹 표준 사인키(가맹점 수정후 고정)
$timestamp = $SignatureUtil->getTimestamp();   // util에 의해서 자동생성

$orderNumber = $mid . "_" . $SignatureUtil->getTimestamp(); // 가맹점 주문번호(가맹점에서 직접 설정)
$last_price = preg_replace("/[^0-9]*/s", "", esc_attr( $cart->total ) ); 
//$price = $last_price;        // 상품가격(특수기호 제외, 가맹점에서 직접 설정)
$price = "1000";

$cardNoInterestQuota = "11-2:3:,34-5:12,14-6:12:24,12-12:36,06-9:12,01-3:4";  // 카드 무이자 여부 설정(가맹점에서 직접 설정)
$cardQuotaBase = "2:3:4:5:6:11:12:24:36";  // 가맹점에서 사용할 할부 개월수 설정
//###################################
// 2. 가맹점 확인을 위한 signKey를 해시값으로 변경 (SHA-256방식 사용)
//###################################
$mKey = $SignatureUtil->makeHash($signKey, "sha256");

$params = array(
    "oid" => $orderNumber,
    "price" => $price,
    "timestamp" => $timestamp
);
$sign = $SignatureUtil->makeSignature($params, "sha256");

/* 기타 */
$siteDomain = "http://192.168.0.2/stdpay/INIStdPaySample"; //가맹점 도메인 입력
// 페이지 URL에서 고정된 부분을 적는다. 
// Ex) returnURL이 http://localhost:8082/demo/INIpayStdSample/INIStdPayReturn.jsp 라면
//                 http://localhost:8082/demo/INIpayStdSample 까지만 기입한다.
?>

<?php do_action( 'hotel_booking_before_checkout_form' ); 
?>
	<div id="hotel-booking-payment">

		<form name="hb-payment-form" id="hb-payment-form" method="post"
		      action="<?php echo isset( $search_page ) ? $search_page : ''; ?>">
			<h3><?php _e( 'Booking Rooms', 'wp-hotel-booking' ); ?></h3>
			<table class="hb_table">
				<thead>
				<tr>
					<th class="hb_room_type"><?php _e( 'Room type', 'wp-hotel-booking' ); ?></th>
					<th class="hb_capacity"><?php _e( 'Capacity', 'wp-hotel-booking' ); ?></th>
					<th class="hb_quantity"><?php _e( 'Quantity', 'wp-hotel-booking' ); ?></th>
					<th class="hb_check_in"><?php _e( 'Check - in', 'wp-hotel-booking' ); ?></th>
					<th class="hb_check_out"><?php _e( 'Check - out', 'wp-hotel-booking' ); ?></th>
					<th class="hb_night"><?php _e( 'Night', 'wp-hotel-booking' ); ?></th>
					<th class="hb_gross_total"><?php _e( 'Gross Total', 'wp-hotel-booking' ); ?></th>
				</tr>
				</thead>

				<?php if ( $rooms = $cart->get_rooms() ) {
					foreach ( $rooms as $cart_id => $room ) {

						/**
						 * @var $room WPHB_Room
						 */
						if ( ( $num_of_rooms = (int) $room->get_data( 'quantity' ) ) == 0 ) {
							continue;
						}
						$cart_extra = $cart->get_extra_packages( $cart_id );
						$sub_total  = $room->get_total( $room->check_in_date, $room->check_out_date, $num_of_rooms, false ); ?>

						<tr class="hb_checkout_item" data-cart-id="<?php echo esc_attr( $cart_id ); ?>">
							<td class="hb_room_type"<?php echo defined( 'WPHB_EXTRA_FILE' ) && $cart_extra ? ' rowspan="' . ( count( $cart_extra ) + 2 ) . '"' : '' ?>>
								<a href="<?php echo esc_url( get_permalink( $room->ID ) ); ?>"><?php echo apply_filters( 'hb_checkout_room_name', $room->name, $room->ID ); ?><?php printf( '%s', $room->capacity_title ? ' (' . $room->capacity_title . ')' : '' ); ?></a>
							</td>
							<td class="hb_capacity"><?php echo sprintf( _n( '%d adult', '%d adults', $room->capacity, 'wp-hotel-booking' ), $room->capacity ); ?> </td>
							<td class="hb_quantity"><?php printf( '%s', $num_of_rooms ); ?></td>
							<td class="hb_check_in"><?php echo date_i18n( hb_get_date_format(), strtotime( $room->get_data( 'check_in_date' ) ) ) ?></td>
							<td class="hb_check_out"><?php echo date_i18n( hb_get_date_format(), strtotime( $room->get_data( 'check_out_date' ) ) ) ?></td>
							<td class="hb_night"><?php echo hb_count_nights_two_dates( $room->get_data( 'check_out_date' ), $room->get_data( 'check_in_date' ) ) ?></td>
							<td class="hb_gross_total">
								<?php echo hb_format_price( $room->total ); ?>
							</td>
						</tr>

						<?php do_action( 'hotel_booking_cart_after_item', $room, $cart_id ); ?>
					<?php }
				} ?>

				<?php do_action( 'hotel_booking_before_cart_total' ); ?>

				<tr class="hb_sub_total">
					<td colspan="7"><?php _e( 'Sub Total', 'wp-hotel-booking' ); ?>
						<span class="hb-align-right hb_sub_total_value">
                        <?php echo hb_format_price( $cart->sub_total ); ?>
                    </span>
					</td>
				</tr>

				<?php if ( $tax = hb_get_tax_settings() ) { ?>
					<tr class="hb_advance_tax">
						<td colspan="7">
							<?php _e( 'Tax', 'wp-hotel-booking' ); ?>
							<?php if ( $tax < 0 ) { ?>
								<span><?php printf( __( '(price including tax)', 'wp-hotel-booking' ) ); ?></span>
							<?php } ?>
							<span class="hb-align-right"><?php echo apply_filters( 'hotel_booking_cart_tax_display', hb_format_price( $cart->total - $cart->sub_total ) ); // abs( $tax * 100 ) . '%' ?></span>
						</td>
					</tr>
				<?php } ?>

				<tr class="hb_advance_grand_total">
					<td colspan="7">
						<?php _e( 'Grand Total', 'wp-hotel-booking' ); ?>
						<span class="hb-align-right hb_grand_total_value"><?php echo hb_format_price( $cart->total ); ?></span>
					</td>
				</tr>

				<?php $advance_payment = ''; ?>
				<?php if ( $advance_payment = $cart->advance_payment ) { ?>
					<tr class="hb_advance_payment">
						<td colspan="7">
							<?php printf( __( 'Advance Payment (%s%% of Grand Total)', 'wp-hotel-booking' ), hb_get_advance_payment() ); ?>
							<span class="hb-align-right hb_advance_payment_value"><?php echo hb_format_price( $advance_payment ); ?></span>
						</td>
					</tr>
					<?php if ( hb_get_advance_payment() < 100 ) { ?>
						<tr class="hb_payment_all">
							<td colspan="7" class="hb-align-right">
								<label class="hb-align-right">
									<input type="checkbox" name="pay_all" />
									<?php _e( 'I want to pay all', 'wp-hotel-booking' ); ?>
								</label>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>

			</table>

			<?php if ( ! is_user_logged_in() && ! hb_settings()->get( 'guest_checkout' ) ) { ?>
				<?php printf( __( 'You have to <strong><a href="%s">login</a></strong> or <strong><a href="%s">register</a></strong> to checkout.', 'wp-hotel-booking' ), wp_login_url( hb_get_checkout_url() ), wp_registration_url() ) ?>
			<?php } else { ?>
				<?php hb_get_template( 'checkout/customer.php', array( 'customer' => $customer ) ); ?>
				<?php hb_get_template( 'checkout/payment-method.php', array( 'customer' => $customer ) ); ?>
				<?php hb_get_template( 'checkout/addition-information.php' ); ?>
				<?php wp_nonce_field( 'hb_customer_place_order', 'hb_customer_place_order_field' ); ?>

				<input type="hidden" name="hotel-booking" value="place_order" />
				<input type="hidden" name="action" value="hotel_booking_place_order" />
				<input type="hidden" name="total_advance"
				       value="<?php echo esc_attr( $cart->advance_payment ? $cart->advance_payment : $cart->total ); ?>" />
				<input type="hidden" name="total_price" value="<?php echo esc_attr( $cart->total ); ?>" />
				
				<input type="hidden" name="currency" value="<?php echo esc_attr( hb_get_currency() ) ?>">
				<?php if ( $tos_page_id = hb_get_page_id( 'terms' ) ) { ?>
					<p>
						<label>
							<input type="checkbox" name="tos" value="1" />
							<?php printf( __( 'I agree with ', 'wp-hotel-booking' ) . '<a href="%s" target="_blank">%s</a>', get_permalink( $tos_page_id ), get_the_title( $tos_page_id ) ); ?>
						</label>
					</p>
				<?php } ?>
				<p>
					<button type="submit" class="hb_button"><?php _e( 'Check out', 'wp-hotel-booking' ); ?></button>
					<button id="SendPayForm_Button"><?php _e( '결제', 'wp-hotel-booking' ); ?></button>
					
				</p>
				

			<?php } ?>
		</form>
		<button id="CookieButton">asdfasdf</button>
		
		
		<table width="650" border="0" cellspacing="0" cellpadding="0" style="display:none; padding:10px;" align="center">
            <tr>
                <td bgcolor="6095BC" align="center" style="padding:10px">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" style="padding:20px">

                        <tr>
						<!--
                            <td>
                                이 페이지는 INIpay Standard 결제요청을 위한 예시입니다.<br/>
                                <br/>
                                결제처리를 위한 action등의 모든 동작은 Import 된 스크립트에 의해 자동처리됩니다.<br/>

                                <br/>
                                Form에 설정된 모든 필드의 name은 대소문자 구분하며,<br/>
                                이 Sample은 결제를 위해서 설정된 Form은 테스트 / 이해돕기를 위해서 모두 type="text"로 설정되어 있습니다.<br/>
                                운영에 적용시에는 일부 가맹점에서 필요에 의해 사용자가 변경하는 경우를 제외하고<br/>
                                모두 type="hidden"으로 변경하여 사용하시기 바랍니다.<br/>

                                <br/>
                                <font color="#336699"><strong>함께 제공되는 매뉴얼을 참조하여 작성 개발하시기 바랍니다.</strong></font>
                                <br/><br/>
                            </td>
							-->
							
                        </tr>
                        
                        <tr>
                            <td>
                                <table>
                                    <tr>
                                        <td style="text-align:left;">
                                            <form id="SendPayForm_id" name="" method="POST">
                                                <!-- 필수 -->
                                                <br/><b>***** 필 수 *****</b>
                                                <div style="border:2px #dddddd double;padding:10px;background-color:#f3f3f3;">

                                                    <!--<br/><b>version</b> :-->
                                                    <input type=hidden style="width:100%;" name="version" value="1.0" >


                                                    <!--<br/><b>mid</b> :-->
                                                    <input type=hidden style="width:100%;" name="mid" value="<?php echo $mid ?>" >

                                                    <!--<br/><b>goodname</b> :-->
                                                    <input type="hidden" style="width:100%;" name="goodname" value="<?php echo apply_filters( 'hb_checkout_room_name', $room->name, $room->ID ); ?>" >

                                                    <!--<br/><b>oid</b> :-->
                                                    <input type="hidden" style="width:100%;" name="oid" value="<?php echo $orderNumber ?>" >

                                                    <!--<br/><b>price</b> :-->
                                                    <input type="hidden" style="width:100%;" name="price" value="<?php echo $price ?>" >

                                                    <!--<br/><b>currency</b> :
                                                    <br/>[WON|USD]-->
                                                    <input type="hidden" style="width:100%;" name="currency" value="WON" >

                                                    <br/><b>이름</b> :
                                                    <br/><input  style="width:100%;" name="buyername" value="" >

                                                    <br/><b>핸드폰</b> :
                                                    <br/><input  style="width:100%;" name="buyertel" value="" >

                                                    <br/><b>이메일</b> :
                                                    <br/><input  style="width:100%;" name="buyeremail" value="" >
													
													<br/><b>이메일쉬</b> :
                                                    <br/><input type=hidden style="width:100%;" name="emailsh" value="testemail" >



                                                    <!-- <br/><b>timestamp</b> : -->
                                                    <input type="hidden"  style="width:100%;" name="timestamp" value="<?php echo $timestamp ?>" >


                                                    <!-- <br/><b>signature</b> : -->
                                                    <input type="hidden" style="width:100%;" name="signature" value="<?php echo $sign ?>" >


                                                    <!--<br/><b>returnUrl</b> :-->
                                                    <br/><input  style="width:100%; display:none;" name="returnUrl" value="<?php echo $siteDomain ?>/INIStdPayReturn.php" >

                                                    <input type="hidden"  name="mKey" value="<?php echo $mKey ?>" >
                                                </div>
												

                                                <br/><br/>
                                                <!--<b>***** 기본 옵션 *****</b>-->
                                                <div style="border:2px #dddddd double;padding:10px;background-color:#f3f3f3; display:none;">
                                                    <b>gopaymethod</b> : 결제 수단 선택
                                                    <br/>ex) Card (계약 결제 수단이 존재하지 않을 경우 에러로 리턴)
                                                    <br/>사용 가능한 입력 값
                                                    <br/>Card,DirectBank,HPP,Vbank,kpay,Swallet,Paypin,EasyPay,PhoneBill,GiftCard,EWallet
                                                    <br/>onlypoint,onlyocb,onyocbplus,onlygspt,onlygsptplus,onlyupnt,onlyupntplus
                                                    <br/><input  style="width:100%;" name="gopaymethod" value="" >
                                                    <br/><br/>

                                                    <br/>
                                                    <b>offerPeriod</b> : 제공기간
                                                    <br/>ex)20150101-20150331, [Y2:년단위결제, M2:월단위결제, yyyyMMdd-yyyyMMdd : 시작일-종료일]
                                                    <br/><input  style="width:100%;" name="offerPeriod" value="2015010120150331" >
                                                    <br/><br/>

                                                    <br/><b>acceptmethod</b> : acceptmethod
                                                    <br/>acceptmethod  ex) CARDPOINT:SLIMQUOTA(코드-개월:개월):no_receipt:va_receipt:vbanknoreg(0):vbank(20150425):va_ckprice:vbanknoreg: 
                                                    <br/>KWPY_TYPE(0):KWPY_VAT(10|0) 기타 옵션 정보 및 설명은 연동정의보 참조 구분자 ":"
                                                    <br/><input style="width:100%;" name="acceptmethod" value="HPP(1):no_receipt:va_receipt:vbanknoreg(0):below1000" >
                                                </div>

                                                <br/><br/>
                                                <!--<b>***** 표시 옵션 *****</b>-->
                                                <div style="border:2px #dddddd double;padding:10px;background-color:#f3f3f3; display:none;">
                                                    <br/><b>languageView</b> : 초기 표시 언어
                                                    <br/>[ko|en] (default:ko)
                                                    <br/><input style="width:100%;" name="languageView" value="" >

                                                    <br/><b>charset</b> : 리턴 인코딩
                                                    <br/>[UTF-8|EUC-KR] (default:UTF-8)
                                                    <br/><input style="width:100%;" name="charset" value="" >

                                                    <br/><b>payViewType</b> : 결제창 표시방법
                                                    <br/>[overlay] (default:overlay)
                                                    <br/><input style="width:100%;" name="payViewType" value="" >

                                                    <br/><b>closeUrl</b> : payViewType='overlay','popup'시 취소버튼 클릭시 창닥기 처리 URL(가맹점에 맞게 설정)
                                                    <br/>close.jsp 샘플사용(생략가능, 미설정시 사용자에 의해 취소 버튼 클릭시 인증결과 페이지로 취소 결과를 보냅니다.)
                                                    <br/><input style="width:100%;" name="closeUrl" value="<?php echo $siteDomain ?>/close.php" >

                                                    <br/><b>popupUrl</b> : payViewType='popup'시 팝업을 띄울수 있도록 처리해주는 URL(가맹점에 맞게 설정)
                                                    <br/>popup.jsp 샘플사용(생략가능,payViewType='popup'으로 사용시에는 반드시 설정)
                                                    <br/><input style="width:100%;" name="popupUrl" value="<?php echo $siteDomain ?>/popup.php" >

                                                </div>

                                                <!-- <b>***** 결제 수단별 옵션 *****</b>
                                                <br/>
                                                <b>-- 카드(간편결제도 사용) --</b> -->
                                                 <div style="border:2px #cccccc solid;padding:10px;background-color:#f3f3f3; display:none;"> -->
                                                    <br/><b>nointerest</b> : 무이자 할부 개월
                                                    <br/>ex) 11-2:3:4,04-2:3:4
                                                    <br/><input  style="width:100%;" name="nointerest" value="<?php echo $cardNoInterestQuota ?>" >

                                                    <br/><b>quotabase</b> : 할부 개월
                                                    <br/>ex) 2:3:4
                                                    <br/><input  style="width:100%;" name="quotabase" value="<?php echo $cardQuotaBase ?>" >	

                                                </div>

                                                <!--<b>-- 가상계좌 --</b>-->
                                                 <div style="border:2px #cccccc solid;padding:10px;background-color:#f3f3f3; display:none;">
                                                    <br/><b>INIregno</b> : 주민번호 설정 기능
                                                    <br/>13자리(주민번호),10자리(사업자번호),미입력시(화면에서입력가능)
                                                    <br/><input  style="width:100%;" name="vbankRegNo" value="" >
                                                </div>

                                                <br/><br/>
                                                <!--<b>***** 추가 옵션 *****</b>-->
                                                <div style="border:2px #dddddd double;padding:10px;background-color:#f3f3f3; display:none;">
                                                    <br/><b>merchantData</b> : 가맹점 관리데이터(1000byte)
                                                    <br/>**인증결과 리턴시 함께 전달됨(한글 지원 안됨, 개인정보 암호화(권장))
                                                    <br/><input  style="width:100%;" name="merchantData" value="" >
                                                </div>
                                            </form>
											<tr>
												<td>
													<button class="SendPayForm_Button" style="padding:10px">결제요청</button>
												</td>
											</tr>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
		
	</div>

<?php do_action( 'hotel_booking_after_checkout_form' ); ?>