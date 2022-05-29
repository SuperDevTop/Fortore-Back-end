@extends($activeTemplate.'layouts.master')

@section('content')
    @include($activeTemplate.'partials.user-breadcrumb')
    <div class="card">
        <div class="card-body">
            <form action="" method="post">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>لتوقيع العقد ادناه يرجى ادخال رمز التاكيد الذي تم ارساله في رسالة</label>
                            <input type="text" name="pin_code" class="text-center form-control form-control-lg">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-md w-100 cmn-btn">توقيع العقد</button>
                </div>
            </form>
            <embed src="{{$pdfUrl}}" width="800px" height="2100px"/>
        </div>
    </div>
@endsection


@push('script')

@endpush

