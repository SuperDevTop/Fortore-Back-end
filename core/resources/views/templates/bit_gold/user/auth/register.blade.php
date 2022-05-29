@extends($activeTemplate.'layouts.frontend')
@section('content')
    @php
        $registerContent = getContent('register.content',true)
    @endphp
    <!-- account section start -->
    <div class="account-section bg_img"
         data-background="{{ getImage('assets/images/frontend/register/'.@$registerContent->data_values->section_bg) }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-7">
                    <div class="account-card">
                        <div class="account-card__header bg_img overlay--one"
                             data-background="{{ getImage('assets/images/frontend/register/'.@$registerContent->data_values->card_bg) }}">
                            <h2 class="section-title">{{ __(@$registerContent->data_values->heading_w) }} <span
                                    class="base--color">{{ __(@$registerContent->data_values->heading_c) }}</span></h2>
                            <p>{{ __(@$registerContent->data_values->sub_heading) }}</p>
                        </div>
                        <div class="account-card__body">
                            <form action="{{ route('user.register')}}" class="mt-4" onsubmit="return submitUserForm();"
                                  method="post">
                                @csrf
                                @if(session()->get('reference') != null)
                                    <div class="form-group">
                                        <label>@lang('Reference')</label>
                                        <input type="text" name="referBy" class="form-control" id="referenceBy"
                                               placeholder="{{trans('Reference By') }}"
                                               value="{{session()->get('reference')}}" readonly>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label>@lang('First Name')</label>
                                    <input type="text" class="form-control" name="firstname"
                                           value="{{ old('firstname') }}" placeholder="@lang('First Name')" required>
                                </div>
                                <div class="form-group">
                                    <label>@lang('Last Name')</label>
                                    <input type="text" class="form-control" name="lastname"
                                           value="{{ old('lastname') }}" placeholder="@lang('Last Name')" required>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Country') }}</label>
                                    <select name="country" id="country" class="form-control">
                                        @foreach($countries as $key => $country)
                                            <option data-mobile_code="{{ $country->dial_code }}"
                                                    value="{{ $country->country }}"
                                                    data-code="{{ $key }}">{{ __($country->country) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>{{ trans('Mobile') }}</label>
                                    <div class="input-group ">
                                        <div class="input-group-prepend">
                            <span class="input-group-text mobile-code">

                            </span>
                                            <input type="hidden" name="mobile_code">
                                        </div>
                                        <input type="text" name="mobile" class="form-control"
                                               value="{{ old('mobile') }}" placeholder="@lang('Your Phone Number')"
                                               required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>المدينة</label>
                                    <div class="input-group ">
                                        <input type="text" name="city" class="form-control" value="{{ old('city') }}"
                                               placeholder="المدينة" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>تاريخ الميلاد</label>
                                    <div class="input-group ">
                                        <input type="date" name="birth_day" class="form-control" value="{{ old('birth_day') }}"
                                               placeholder="تاريخ الميلا" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>@lang('Email Address')</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                           placeholder="@lang('Enter email address')" required>
                                </div>
                                <div class="form-group">
                                    <label>@lang('User Name')</label>
                                    <input type="text" name="username" class="form-control"
                                           value="{{ old('username') }}" placeholder="@lang('User Name')" required>
                                </div>
                                <div class="form-group">
                                    <label>@lang('Password')</label>
                                    <input type="password" name="password" class="form-control"
                                           placeholder="@lang('Enter password')" required>
                                </div>
                                <div class="form-group">
                                    <label>{{ trans('Confirm Password') }}</label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                           placeholder="@lang('Confirm Password')" required>
                                </div>
                                <div class="form-group d-flex justify-content-center">
                                    @php echo recaptcha() @endphp
                                </div>
                                @include($activeTemplate.'partials.custom-captcha')
                                @php
                                    $links = getContent('links.element','','',1)
                                @endphp
                                <div class="form-row mt-2">
                                    <div class="col-md-12">
                                        <input type="checkbox" name="terms" required> <span
                                            class="f-size-14 ml-2">@lang('I agree with') @foreach($links as $link)
                                                <a class="base--color"
                                                   href="{{ route('linkDetails',[slug($link->data_values->title),$link->id]) }}"> @lang($link->data_values->title)</a>
                                                @if(!$loop->last) , @endif @endforeach</span>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="cmn-btn">@lang('SignUp Now')</button>
                                </div>
                                <div class="form-row mt-2">
                                    <div class="col-sm-6">
                                        <p class="f-size-14">@lang('Have an account?') <a
                                                href="{{ route('user.login') }}" class="base--color">@lang('Login')</a>
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- account section end -->
@endsection
@push('style')
    <style>
        .input-group-text {
            color: #fff;
        }
    </style>
@endpush
@push('script')
    <script>
        "use strict";
        @if($mobile_code)
        $(`option[data-code={{ $mobile_code }}]`).attr('selected', '');
        @endif

        $('select[name=country]').change(function () {
            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
        });
        $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
        $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));

        function submitUserForm() {
            var response = grecaptcha.getResponse();
            if (response.length == 0) {
                document.getElementById('g-recaptcha-error').innerHTML = '<span style="color:red;">@lang("Captcha field is required.")</span>';
                return false;
            }
            return true;
        }

        function verifyCaptcha() {
            document.getElementById('g-recaptcha-error').innerHTML = '';
        }
    </script>
@endpush
