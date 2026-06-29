import { render } from 'solid-js/web';
import FeaturedImagePicker from './components/FeaturedImagePicker';

const mountEl = document.getElementById('featured-image-picker');
if (mountEl) {
  render(() => <FeaturedImagePicker />, mountEl);
}
