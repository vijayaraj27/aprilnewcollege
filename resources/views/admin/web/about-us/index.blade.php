@extends('admin.layouts.master')
@section('title', $title)
@section('content')
<!-- Start Content-->
<div class="main-body">
    <div class="page-wrapper">
        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">


                <div class="card">
                    <div class="card-header">
                        <h3 class="bold">Department About Us Form</h3>

                        <?php 
//                        
//  echo "<pre>";
//   echo     print_r($row);
// //echo $contact = json_decode($row->contact, true);
//  echo "</pre>";
//  exit;
$sectionAbout = $row ? json_decode($row->sectionAbout, true) : [];
//$image_file = $sectionAbout ? $sectionAbout['image_file'] : '';
if (isset($_FILES['sectionAbout']['name']['image_file'])) {
    // Handle the file upload, e.g., move it to a specific directory
    $image_file = $_FILES['sectionAbout']['name']['image_file'];
}
$title = $sectionAbout ? $sectionAbout['title'] : '';
$description = $sectionAbout ? $sectionAbout['description'] : '';
$contact = $row ? json_decode($row->contact, true) : [];                        
                        ?>
                    </div>
                    <div class="card-block">
                        <form id="aboutUsForm" class="needs-validation" method="POST"
                            action="{{ route($route . '.store', ['departmentId' => $departmentId]) }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <!-- Slider Section -->
                                {{-- <div id="sliderContainer" style="display:none">
                                        <h4>Slider</h4>
                                        <div class="slider-entry row">
                                                <div class="form-group col-md-6"> <input type="text" class="form-control" name="slider[0][image_file]" placeholder="Image File" required> </div>
                                                <div class="form-group col-md-6">  <input type="text" class="form-control" name="slider[0][title]" placeholder="Title" required> </div>
                                                <div class="form-group col-md-12"> <textarea name="slider[0][description]" class="form-control texteditor" placeholder="Description" required></textarea> </div>
                                                <div class="form-group col-md-6"> <input type="text" class="form-control" name="slider[0][cta_button]" placeholder="CTA Button" required> </div>
                                                <div class="form-group col-md-6"> <input type="text" class="form-control" name="slider[0][cta_url]" placeholder="CTA URL" required> </div>
                                                <div class="form-group col-md-12 text-end"> <button type="button" class="removeSliderBtn btn btn-danger w-auto" onclick="removeSlider(this)">Remove</button> </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 text-center" style="display:none">  <button type="button" id="addSliderBtn" class="btn btn-info" onclick="addSlider()"><i class="fa fa-plus"></i> <i class="fa fa-plus"></i> Add Slider</button> </div> --}}

                                <!-- Section About -->
                                <input type="hidden" name="id" value="{{ isset($row->id)?$row->id:'' }}" />
                                <h4>About Section</h4>
                                <?php
// if (isset($_FILES['sectionAbout']['name']['image_file'])) {
//     // Handle the file upload, e.g., move it to a specific directory
//     $imageFile = $_FILES['sectionAbout']['name']['image_file'];
   
// }
// else{
//     $imageFile = "";
// }

?>
                                <div class="form-group col-md-6">

                                    <label for="photo">{{ __('field_photo') }}:
                                        <span>{{ __('image_size', ['height' => 400, 'width' => 400]) }}</span>
                                        <span>*</span></label>
                                    <input type="file" class="form-control" name="sectionAbout[image_file]"
                                        placeholder="Image File" value="">
                                    <img alt="Section About Image" width="70" height="70"
                                        src="{{ isset($sectionAbout['image_file']) ? $baseurl.'uploads/about-us/' . $sectionAbout['image_file'] : '' }}" />
                                </div>
                                <div class="form-group col-md-6">
                                    <label> Title </label>
                                    <input type="text" class="form-control" name="sectionAbout[title]"
                                        placeholder="Title" required value="{{ isset($title)?$title:'' }}">
                                </div>
                                <div class="form-group col-md-12"> <textarea class="form-control texteditor"
                                        name="sectionAbout[description]" placeholder="Description"
                                        required>{{ isset($description)?$description:'' }}</textarea> </div>

                                <!-- Vision -->
                                <h4>Vision</h4>
                                <div class="form-group col-md-12"> <textarea class="form-control texteditor"
                                        name="vision" placeholder="Vision"
                                        required>{{ isset($row->vision)?$row->vision:'' }}</textarea> </div>

                                <!-- Mission -->
                                <h4>Mission</h4>
                                <div class="form-group col-md-12"> <textarea class="form-control texteditor"
                                        name="mission" placeholder="Mission"
                                        required>{{ isset($row->mission)?$row->mission:'' }}</textarea> </div>

                                <!-- Core Values -->
                                <h4>Core Values</h4>
                                <div class="form-group col-md-12"> <textarea class="form-control texteditor"
                                        name="coreValue" placeholder="Core Values"
                                        required>{{ isset($row->coreValue) ? $row->coreValue:'' }}</textarea> </div>

                                <!-- Department Section Image -->
                                <h4>Department Section Image</h4>
                                <div class="form-group col-md-6"> <input type="text" class="form-control texteditor"
                                        name="departmentSectionImage"
                                        value="{{$row ? $row->departmentSectionImage : ''}}" placeholder="">
                                </div>

                                <!-- Testimonials -->
                                {{-- 
                                    <div id="testimonialContainer" style="display:none">
                                        <h4>Testimonials</h4>
                                        <div class="testimonial-entry">
                                            <div class="form-group col-md-6">  <input class="form-control" type="text" name="testimonial[0][name]" placeholder="Name" required> </div> 
                                            <div class="form-group col-md-12">  <textarea class="form-control texteditor"  name="testimonial[0][testimonial]" placeholder="Testimonial" required></textarea> </div>
                                            <div class="form-group col-md-12 text-end">  <button type="button " class="removeTestimonialBtn  btn btn-danger w-auto" onclick="removeTestimonial(this)">Remove</button> </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12 text-center" style="display:none">  <button type="button" id="addTestimonialBtn" class="btn btn-info" onclick="addTestimonial()"><i class="fa fa-plus"></i> <i class="fa fa-plus"></i> Add Testimonial</button> </div>
                                    --}}

                                <div class="form-group col-md-12">
                                    <div class="alert alert-info mb-0">
                                        Programme Educational Objectives, Programme Outcomes, and Programme Specific Outcomes are managed from the department dashboard links (separate CRUD lists).
                                    </div>
                                </div>

                                <!-- Contact -->
                                <h4>Contact Information</h4>
                                <div class="form-group col-md-6"> <input class="form-control" type="text"
                                        name="contact[name]" value="{{$contact ? $contact['name'] : ''}}"
                                        placeholder="Name" required /> </div>
                                <div class="form-group col-md-6"> <input class="form-control" type="email"
                                        name="contact[email]" value="{{$contact ? $contact['email'] : ''}}"
                                        placeholder="Email" required /> </div>
                                <div class="form-group col-md-6"> <input class="form-control" type="text"
                                        name="contact[phone]" value="{{$contact ? $contact['phone'] : ''}}"
                                        placeholder="Phone" required /> </div>

                                <div class="form-group col-md-12"> <button type="submit"
                                        class="btn btn-success">Submit</button> </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- End Content-->
<script>
function addSlider() {
    const sliderContainer = document.getElementById('sliderContainer');
    
    // Find the highest existing index to avoid conflicts
    let maxIndex = -1;
    const inputs = sliderContainer.querySelectorAll('input[name^="slider"]');
    inputs.forEach(input => {
        const match = input.name.match(/slider\[(\d+)\]/);
        if (match) {
            maxIndex = Math.max(maxIndex, parseInt(match[1]));
        }
    });
    const index = maxIndex + 1;
    const newSlider = `
            <div class="slider-entry row">
                <div class="form-group col-md-6">    <input type="text" class="form-control" name="slider[${index}][image_file]" placeholder="Image File" required> </div>
                <div class="form-group col-md-6">    <input type="text" class="form-control" name="slider[${index}][title]" placeholder="Title" required> </div>
                <div class="form-group col-md-12">    <textarea class="form-control texteditor" name="slider[${index}][description]" placeholder="Description" required></textarea> </div>
                <div class="form-group col-md-6">    <input type="text" class="form-control" name="slider[${index}][cta_button]" placeholder="CTA Button" required> </div>
                <div class="form-group col-md-6">    <input type="text" class="form-control" name="slider[${index}][cta_url]" placeholder="CTA URL" required> </div>
                <div class="form-group text-end">    <button type="button" class="removeSliderBtn  btn btn-danger w-auto" onclick="removeSlider(this)">Remove</button> </div>
            </div>`;
    sliderContainer.insertAdjacentHTML('beforeend', newSlider);
    initTextEditor();
}

function removeSlider(btn) {
    const sliderEntry = btn.closest('.slider-entry');
    sliderEntry.remove();
}

function addTestimonial() {
    const testimonialContainer = document.getElementById('testimonialContainer');
    
    // Find the highest existing index to avoid conflicts
    let maxIndex = -1;
    const inputs = testimonialContainer.querySelectorAll('input[name^="testimonial"]');
    inputs.forEach(input => {
        const match = input.name.match(/testimonial\[(\d+)\]/);
        if (match) {
            maxIndex = Math.max(maxIndex, parseInt(match[1]));
        }
    });
    const index = maxIndex + 1;
    const newTestimonial = `
            <div class="testimonial-entry">
                <div class="form-group col-md-6">  <input  class="form-control"  type="text" name="testimonial[${index}][name]" placeholder="Name" required> </div>
                <div class="form-group col-md-12">  <textarea  class="form-control texteditor"  name="testimonial[${index}][testimonial]" placeholder="Testimonial" required></textarea> </div>
                <div class="form-group col-md-12 text-end">  <button type="button" class="removeTestimonialBtn  btn btn-danger w-auto" onclick="removeTestimonial(this)">Remove</button> </div>
            </div>`;
    testimonialContainer.insertAdjacentHTML('beforeend', newTestimonial);
    initTextEditor();
}

function removeTestimonial(btn) {
    const testimonialEntry = btn.closest('.testimonial-entry');
    testimonialEntry.remove();
}


</script>
@endsection