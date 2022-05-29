@extends($activeTemplate.'layouts.master')

@section('content')

    @include($activeTemplate.'partials.user-breadcrumb')
    <section class="feature-section pt-150 pb-150">
        <div class="container">
            <div class="row justify-content-center mt-2">

                <div class="col-md-12">
                    <div class="text-md-right text-center mb-5 text-white">
                        الرصيد: {{ $user->loyaltyPointsBalance() }}
                    </div>
                </div>


                <div class="col-md-12">


                    <div class="table-responsive--sm neu--table">

                        <table class="table table-striped text-white">
                            <thead>
                            <tr>
                                <th scope="col">@lang('Date')</th>
                                <th scope="col">@lang('Details')</th>
                                <th scope="col">@lang('Amount')</th>
                                <th scope="col">@lang('Type')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($loyalty_points as $k=>$data)
                                <tr>
                                    <td data-label="@lang('Date')">
                                        {{showDateTime($data->created_at)}}
                                    </td>
                                    <td>{{$data->description}}</td>
                                    <td>
                                       {{ $data->amount }}
                                    </td>
                                    <td>
                                        <strong>@lang($data->type)</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">{{__($empty_message)}}</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table>

                        {{$loyalty_points->links()}}
                    </div>

                </div>
            </div>
        </div>
    </section>


@endsection


@push('script')

@endpush

