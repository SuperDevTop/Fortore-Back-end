@extends($activeTemplate.'layouts.master')
@section('content')
    @include($activeTemplate.'partials.user-breadcrumb')

    <section class="cmn-section">
        <div class="container">
            <div class="card">
                <form action="" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                       @lang("Pin Code") :  {{ $pin_code }}
                    </div>
                    <div class="card-footer">
                        <button type="submit"   class="btn btn-md w-100 cmn-btn">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection


@push('script')

@endpush

