@extends($activeTemplate.'layouts.master')
@section('content')
@include($activeTemplate.'partials.user-breadcrumb')
<div class="pt-120 pb-120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12 pl-lg-5 mt-lg-0 mt-5">
                <div class="row mb-none-30">
                    <div class="col-md-12 mb-4">
                        <label>@lang('Referral Link')</label>
                        <div class="input-group">
                            <input type="text" name="text" class="form-control" id="referralURL"
                                value="{{route('user.refer.register',[Auth::user()->username])}}" readonly>
                            <div class="input-group-append">
                                <span class="input-group-text copytext copyBoard" id="copyBoard"> <i
                                        class="fa fa-copy"></i> </span>
                            </div>
                        </div>
                    </div>


                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">@lang('Total Invest')</span>
                                <h4 class="currency-amount">{{ getAmount($totalInvest) }} {{
                                    $general->cur_sym }}</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">الاسهم</span>
                                <h4 class="currency-amount">20000</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">@lang('Interest Wallet Balance')</span>
                                <h4 class="currency-amount">{{ getAmount($totalDailyRevenue) }} {{ $general->cur_sym
                                    }}</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>


                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">@lang('Total Deposit')</span>
                                <h4 class="currency-amount">{{
                                    getAmount($totalInterestAmount) }} {{ $general->cur_sym
                                    }}</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">@lang('Total Withdraw')</span>
                                <h4 class="currency-amount">{{
                                    getAmount($totalWithdrawals) }} {{
                                    $general->cur_sym }}</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    {{-- <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">@lang('Referral Earnings')</span>
                                <h4 class="currency-amount">{{ getAmount($user->commissions->sum('commission_amount'))
                                    }} {{ $general->cur_sym }}</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div> --}}
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <span class="caption">المشتريات</span>
                                <h4 class="currency-amount">{{ getAmount($user->commissions->sum('commission_amount'))
                                    }} {{ $general->cur_sym }}</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="{{route('user.loyalty_points')}}"> <span class="caption">اجمالي نقاط
                                        الولاء</span></a>
                                <h4 class="currency-amount">{{ getAmount($user->loyaltyPointsBalance()) }} نقطة</h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30" style="padding:20px 10px;">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="#"> <span class="caption">شراء حصص</span></a>
                                <h4 class="currency-amount"></h4>
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="#"> <span class="caption">بياناتي</span></a>
                                {{-- <h4 class="currency-amount">0</h4> --}}
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="#"> <span class="caption">المعاملات</span></a>
                                {{-- <h4 class="currency-amount">0</h4> --}}
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="#"> <span class="caption">الايداع</span></a>
                                {{-- <h4 class="currency-amount">0</h4> --}}
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="#"> <span class="caption">تذكرة الدعم</span></a>
                                {{-- <h4 class="currency-amount">0</h4> --}}
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget  d-flex flex-wrap">
                            <div class="col-8">
                                <a href="#"> <span class="caption">تغيير كلمة المرور</span></a>
                                {{-- <h4 class="currency-amount">0</h4> --}}
                            </div>

                        </div><!-- d-widget-two end -->
                    </div>
                </div><!-- row end -->
                {{-- <div class="row mt-50">--}}
                    {{-- <div class="col-lg-12">--}}
                        {{-- <div class="table-responsive--md">--}}
                            {{-- <table class="table style--two">--}}
                                {{-- <thead>--}}
                                    {{-- <tr>--}}
                                        {{-- <th>@lang('Date')</th>--}}
                                        {{-- <th>@lang('Transaction ID')</th>--}}
                                        {{-- <th>@lang('Amount')</th>--}}
                                        {{-- <th>@lang('Wallet')</th>--}}
                                        {{-- <th>@lang('Details')</th>--}}
                                        {{-- <th>@lang('Post Balance')</th>--}}
                                        {{-- </tr>--}}
                                    {{-- </thead>--}}
                                {{-- <tbody>--}}
                                    {{-- @forelse($transactions as $trx)--}}
                                    {{-- <tr>--}}
                                        {{-- <td data-label="@lang('Date')">{{ showDatetime($trx->created_at,'d/m/Y') }}
                                        </td>--}}
                                        {{-- <td data-label="@lang('Transaction ID')"><span class="text-primary">{{
                                                $trx->trx--}}
                                                {{-- }}</span></td>--}}

                                        {{-- <td data-label="@lang('Amount')">--}}
                                            {{-- @if($trx->trx_type == '+')--}}
                                            {{-- <span class="text-success">+ {{ getAmount($trx->amount) }} {{--}}
                                                {{-- __($general->cur_sym) }}</span>--}}
                                            {{-- @else--}}
                                            {{-- <span class="text-danger">- {{ getAmount($trx->amount) }} {{--}}
                                                {{-- __($general->cur_sym) }}</span>--}}
                                            {{-- @endif--}}
                                            {{-- </td>--}}
                                        {{-- <td data-label="@lang('Wallet')">--}}
                                            {{-- @if($trx->wallet_type == 'deposit_wallet')--}}
                                            {{-- <span class="badge badge-info">@lang('Deposit Wallet')</span>--}}
                                            {{-- @else--}}
                                            {{-- <span class="badge badge-primary">@lang('Interest Wallet')</span>--}}
                                            {{-- @endif--}}
                                            {{-- </td>--}}
                                        {{-- <td data-label="@lang('Details')">{{$trx->details}}</td>--}}
                                        {{-- <td data-label="@lang('Post Balance')">--}}
                                            {{-- <span> {{ getAmount($trx->post_balance) }} {{ __($general->cur_sym)--}}
                                                {{-- }}</span>--}}
                                            {{-- </td>--}}
                                        {{-- </tr>--}}
                                    {{-- @empty--}}
                                    {{-- <tr>--}}
                                        {{-- <td colspan="100%" class="text-center">{{ __('No Transaction Found') }}
                                        </td>--}}
                                        {{-- </tr>--}}
                                    {{-- @endforelse--}}
                                    {{-- </tbody>--}}
                                {{-- </table>--}}
                            {{-- </div>--}}
                        {{-- </div>--}}
                    {{-- </div>--}}
            </div>
        </div>
    </div>
</div>
@endsection
@push('style')
<style type="text/css">
    #copyBoard {
        cursor: pointer;
    }
</style>
@endpush
@push('script')
<script>
    $('.copyBoard').click(function () {
            "use strict";
            var copyText = document.getElementById("referralURL");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            /*For mobile devices*/
            document.execCommand("copy");
            iziToast.success({message: "Copied: " + copyText.value, position: "topRight"});
        });
</script>
@endpush