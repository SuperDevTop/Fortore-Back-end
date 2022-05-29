@extends($activeTemplate.'layouts.master')
@section('content')
    @include($activeTemplate.'partials.user-breadcrumb')
    <section class="cmn-section">

        <div class="container">
            <div class="row  justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <ul class="list-group text-center">

                                <li class="list-group-item p-prev-list">
                                    <h3>{{$page_title}}</h3>
                                </li>
                                <p class="list-group-item">
                                    {{ $message }}
                                </p>

                            </ul>


                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection


@push('style')
    <style type="text/css">
        .p-prev-list img {
            max-width: 100px;
            max-height: 100px;
            margin: 0 auto;
        }
    </style>
@endpush
