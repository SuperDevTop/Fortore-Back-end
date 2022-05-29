@extends($activeTemplate.'layouts.master')
@section('content')
    @include($activeTemplate.'partials.user-breadcrumb')

    <div class="signup-wrapper pt-150 pb-150">
        <div class="container">
            <div class="row justify-content-center">


                <div class="col-xl-8">
                    <div class="signup-form-area">

                        <form class="signup-form" action="" method="post">
                            @csrf
                            <div class="card-body text-white">
                                @lang("Pin Code") :  {{ $pin_code }}
                            </div>
                            <div class="form-row">

                                <div class="col-lg-12 form-group">
                                    <button type="submit" class="btn btn-primary btn-small w-100">{{trans('Update')}}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script')

@endpush

