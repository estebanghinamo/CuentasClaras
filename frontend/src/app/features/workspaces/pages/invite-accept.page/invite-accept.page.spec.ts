import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { InviteAcceptPage } from './invite-accept.page';

describe('InviteAcceptPage', () => {
  let component: InviteAcceptPage;
  let fixture: ComponentFixture<InviteAcceptPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [InviteAcceptPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ code: 'abc' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(InviteAcceptPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
